<?php

use App\Enums\WorkMode;
use App\Services\JobPosting\JobPostingExtractor;

function pageWithJsonLd(array|string $jsonLd, string $head = ''): string
{
    $json = is_string($jsonLd) ? $jsonLd : json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    return <<<HTML
        <!doctype html>
        <html><head><title>Fallback title</title>{$head}
        <script type="application/ld+json">{$json}</script>
        </head><body><h1>Job</h1></body></html>
        HTML;
}

const JOB_URL = 'https://www.jobs.example/vacancy/42';

test('it reads a schema.org JobPosting', function () {
    $html = pageWithJsonLd([
        '@context' => 'https://schema.org',
        '@type' => 'JobPosting',
        'title' => 'Middle Laravel Developer',
        'hiringOrganization' => ['@type' => 'Organization', 'name' => 'Moravia Data Labs'],
        'description' => '<p>We build <b>APIs</b>.</p><ul><li>Laravel</li><li>Vue</li></ul>',
        'jobLocation' => ['@type' => 'Place', 'address' => ['addressLocality' => 'Brno', 'addressCountry' => 'CZ']],
        'baseSalary' => ['@type' => 'MonetaryAmount', 'currency' => 'czk', 'value' => ['minValue' => 70000, 'maxValue' => 90000.5]],
    ]);

    $posting = (new JobPostingExtractor)->extract($html, JOB_URL);

    expect($posting)
        ->title->toBe('Middle Laravel Developer')
        ->company->toBe('Moravia Data Labs')
        ->location->toBe('Brno')
        ->workMode->toBeNull()
        ->salaryMin->toBe(70000)
        ->salaryMax->toBe(90001)
        ->salaryCurrency->toBe('CZK')
        ->description->toBe("We build APIs.\n\n• Laravel\n• Vue");
});

test('it finds the posting inside an @graph and decodes entity-encoded descriptions', function () {
    $html = pageWithJsonLd([
        '@context' => 'https://schema.org',
        '@graph' => [
            ['@type' => 'WebSite', 'name' => 'Jobs'],
            [
                '@type' => 'JobPosting',
                'title' => 'Python Developer',
                'hiringOrganization' => 'Spilberk AI',
                'description' => '&lt;p&gt;Python &amp;amp; RAG&lt;/p&gt;',
                'jobLocationType' => 'TELECOMMUTE',
                'baseSalary' => ['currency' => 'EUR', 'value' => 4000],
            ],
        ],
    ]);

    expect((new JobPostingExtractor)->extract($html, JOB_URL))
        ->company->toBe('Spilberk AI')
        ->description->toBe('Python & RAG')
        ->workMode->toBe(WorkMode::Remote)
        ->salaryMin->toBe(4000)
        ->salaryMax->toBe(4000);
});

test('it skips other JSON-LD blocks and uses the host when the company is missing', function () {
    $html = pageWithJsonLd(['@type' => 'BreadcrumbList'])
        .'<script type="application/ld+json">[{"@type":"JobPosting","title":"QA Engineer"}]</script>';

    expect((new JobPostingExtractor)->extract($html, JOB_URL))
        ->title->toBe('QA Engineer')
        ->company->toBe('jobs.example');
});

test('broken JSON-LD falls back to Open Graph tags', function () {
    $html = pageWithJsonLd('{ not json', <<<'HTML'
        <meta property="og:title" content="Vue.js Developer">
        <meta property="og:site_name" content="Copperleaf Studio">
        <meta property="og:description" content="Join our frontend team.">
        HTML);

    expect((new JobPostingExtractor)->extract($html, JOB_URL))
        ->title->toBe('Vue.js Developer')
        ->company->toBe('Copperleaf Studio')
        ->description->toBe('Join our frontend team.')
        ->salaryMin->toBeNull();
});

test('a page without a title gives nothing', function () {
    expect((new JobPostingExtractor)->extract('<html><body><p>Hello</p></body></html>', JOB_URL))->toBeNull();
});

test('non-latin text survives parsing', function () {
    $html = pageWithJsonLd(['@type' => 'JobPosting', 'title' => 'Розробник Laravel', 'hiringOrganization' => ['name' => 'Компанія']]);

    expect((new JobPostingExtractor)->extract($html, JOB_URL))
        ->title->toBe('Розробник Laravel')
        ->company->toBe('Компанія');
});
