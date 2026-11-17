<?php

namespace App\Services\JobPosting;

use App\Enums\WorkMode;
use Dom\HTMLDocument;

/**
 * Pulls vacancy data out of a job page's HTML.
 *
 * Most job boards embed schema.org JobPosting as JSON-LD for Google for Jobs, which gives clean,
 * structured data. Pages without it fall back to Open Graph / meta tags (title and description only).
 */
class JobPostingExtractor
{
    public function extract(string $html, string $url): ?JobPosting
    {
        $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);

        foreach ($document->querySelectorAll('script[type="application/ld+json"]') as $script) {
            $data = json_decode($script->textContent, true);
            $schema = is_array($data) ? $this->findJobPosting($data) : null;

            if ($schema !== null && ($posting = $this->fromSchema($schema, $url)) !== null) {
                return $posting;
            }
        }

        return $this->fromMetaTags($document, $url);
    }

    /**
     * JSON-LD may hold a single object, a list of objects, or an "@graph" of them.
     *
     * @param  array<mixed>  $node
     * @return array<string, mixed>|null
     */
    private function findJobPosting(array $node, int $depth = 0): ?array
    {
        if ($depth > 5) {
            return null;
        }

        if (in_array('JobPosting', (array) ($node['@type'] ?? []), true)) {
            return $node;
        }

        $children = array_is_list($node) ? $node : (array) ($node['@graph'] ?? []);

        foreach ($children as $child) {
            if (is_array($child) && ($found = $this->findJobPosting($child, $depth + 1)) !== null) {
                return $found;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    private function fromSchema(array $schema, string $url): ?JobPosting
    {
        $title = $this->text($schema['title'] ?? $schema['name'] ?? null);

        if ($title === null) {
            return null;
        }

        $organization = $schema['hiringOrganization'] ?? null;
        $company = $this->text(is_array($organization) ? ($organization['name'] ?? null) : $organization);

        [$salaryMin, $salaryMax, $currency] = $this->salary($schema['baseSalary'] ?? null);

        return new JobPosting(
            title: $title,
            company: $company ?? $this->host($url),
            description: $this->htmlToText($schema['description'] ?? null),
            location: $this->location($schema['jobLocation'] ?? null),
            workMode: in_array('TELECOMMUTE', (array) ($schema['jobLocationType'] ?? []), true) ? WorkMode::Remote : null,
            salaryMin: $salaryMin,
            salaryMax: $salaryMax,
            salaryCurrency: $currency,
        );
    }

    private function fromMetaTags(HTMLDocument $document, string $url): ?JobPosting
    {
        $meta = fn (string $selector) => $this->text($document->querySelector($selector)?->getAttribute('content'));

        $title = $meta('meta[property="og:title"]') ?? $this->text($document->querySelector('title')?->textContent);

        if ($title === null) {
            return null;
        }

        return new JobPosting(
            title: $title,
            company: $meta('meta[property="og:site_name"]') ?? $this->host($url),
            description: $meta('meta[property="og:description"]') ?? $meta('meta[name="description"]'),
        );
    }

    private function location(mixed $jobLocation): ?string
    {
        $place = is_array($jobLocation) && array_is_list($jobLocation) ? ($jobLocation[0] ?? null) : $jobLocation;
        $address = is_array($place) ? ($place['address'] ?? null) : $place;

        if (! is_array($address)) {
            return $this->text($address);
        }

        $country = $address['addressCountry'] ?? null;

        return $this->text($address['addressLocality'] ?? null)
            ?? $this->text($address['addressRegion'] ?? null)
            ?? $this->text(is_array($country) ? ($country['name'] ?? null) : $country);
    }

    /**
     * @return array{0: ?int, 1: ?int, 2: ?string} min, max, ISO currency code
     */
    private function salary(mixed $baseSalary): array
    {
        if (! is_array($baseSalary)) {
            return [null, null, null];
        }

        $value = $baseSalary['value'] ?? null;
        $amount = fn (mixed $number) => is_numeric($number) ? (int) round((float) $number) : null;

        [$min, $max] = is_array($value)
            ? [$amount($value['minValue'] ?? $value['value'] ?? null), $amount($value['maxValue'] ?? $value['value'] ?? null)]
            : [$amount($value), $amount($value)];

        $currency = strtoupper((string) ($baseSalary['currency'] ?? ''));

        return [$min, $max, preg_match('/^[A-Z]{3}$/', $currency) ? $currency : null];
    }

    /**
     * Descriptions are HTML, often entity-encoded inside the JSON; keep paragraphs and list items as lines.
     */
    private function htmlToText(mixed $html): ?string
    {
        if (! is_string($html)) {
            return null;
        }

        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $html = preg_replace('~<li[^>]*>~i', "\n• ", $html);
        $html = preg_replace('~<br\s*/?>|</(p|div|ul|ol|h[1-6])>~i', "\n", (string) $html);
        $text = html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $lines = array_map(fn (string $line) => trim(preg_replace('/[ \t\x{00A0}]+/u', ' ', $line) ?? ''), explode("\n", $text));
        $text = trim(preg_replace("/\n{3,}/", "\n\n", implode("\n", $lines)) ?? '');

        return $text === '' ? null : $text;
    }

    private function text(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');

        return $value === '' ? null : $value;
    }

    private function host(string $url): string
    {
        return preg_replace('/^www\./', '', (string) parse_url($url, PHP_URL_HOST));
    }
}
