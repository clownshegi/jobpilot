# Design decisions

Short records of non-obvious choices: the context, what was decided, and what else was considered.

## 1. Vacancy and application are separate models

**Context.** A vacancy is data about a job (title, company, salary, description); an application is my
progress with it (status, position on the board, notes, history).

**Decision.** `Vacancy` has one `JobApplication`. The class is not called `Application` to avoid
confusion with `Illuminate\Foundation\Application`.

**Alternatives.** A `status` column on `vacancies` — simpler, but mixes posting data that the AI service
will read with pipeline state that only the board cares about.

## 2. Board order is an integer `position` per column, renumbered on every move

**Context.** Drag and drop needs a stable order inside each status column.

**Decision.** `MoveJobApplication` inserts the card at the requested index and renumbers the source and
target columns inside one transaction, with `SELECT … FOR UPDATE` on the affected rows.

**Alternatives.** Fractional or gapped positions (no renumbering, occasional rebalancing) scale better for
thousands of cards per column; a personal job search has dozens, so plain renumbering is simpler and
always consistent.

## 3. Status history is its own table

**Decision.** Every status change writes an `application_status_changes` row (from → to, timestamp).

**Why.** It powers the history on the vacancy page now and a funnel later ("how many applications reached
an interview, and how long it took").

## 4. Import by link reads schema.org `JobPosting` (JSON-LD) first

**Context.** Every job board has different markup; scraping each one breaks with every redesign.

**Decision.** Most job boards embed schema.org `JobPosting` as JSON-LD for Google for Jobs, so the importer
reads that (title, company, location, salary, remote flag, description). Pages without it fall back to
Open Graph / meta tags. Parsing uses PHP 8.4's HTML5 parser (`Dom\HTMLDocument`).

**Alternatives.** Per-site scrapers (fragile); sending the page to an LLM to extract fields (planned as a
fallback once the AI service exists); a browser extension that sends the page from the user's own session
(the only way around sites that block bots, such as LinkedIn).

## 5. SSRF protection for imports

**Context.** The server fetches a URL that the user typed. Without checks, an import could be pointed at
`http://127.0.0.1:6379`, the Docker network or a cloud metadata endpoint (`169.254.169.254`).

**Decision.** `UrlGuard` allows only http(s) URLs whose host resolves exclusively to public addresses. It
runs at validation time, again right before the fetch, and on every redirect target (Guzzle's
`on_redirect` hook), since a public page can redirect to an internal one.

**Known limit.** DNS rebinding between the check and the request is still possible; a full fix would pin
the resolved IP for the connection. Acceptable for a personal tool.

## 6. Horizon and Reverb run in Docker

**Context.** Horizon requires the `pcntl` and `posix` extensions, which don't exist for PHP on Windows.

**Decision.** A small `php:8.4-cli` image runs `horizon` and `reverb:start` with the project mounted, so code
changes need no rebuild. `composer.json` declares `ext-pcntl`/`ext-posix` under `config.platform`
so Composer can install Horizon on a Windows host that never runs it.

## 7. Real-time events carry little or no data

**Decision.** `BoardChanged` has no payload: open boards just re-fetch their columns through Inertia's
partial reload. `VacancyImportUpdated` carries only the import's status. Both are broadcast on the user's
private channel and implement `ShouldDispatchAfterCommit`, so nobody is told about data that a rolled-back
transaction never saved.

**Why.** The server stays the single source of truth for what a board looks like, and there is no
client-side merging logic to get wrong. The cost is one extra request per change, which is fine at this scale.
