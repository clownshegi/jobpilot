# JobPilot

![CI](https://github.com/clownshegi/jobpilot/actions/workflows/ci.yml/badge.svg)

A job-search tracker with AI matching. I built it to automate my own job search:
vacancies, companies, applications and their statuses on a kanban board, plus a Python service
that compares my CV with a vacancy and explains what matches and what is missing.

> 🚧 Work in progress. See [Roadmap](#roadmap).

## Features

- [ ] Vacancies, companies, applications, statuses
- [ ] Kanban board for the application pipeline
- [ ] Import a vacancy by URL (queued job, Horizon + Redis)
- [ ] Real-time updates (Reverb)
- [ ] AI matching: CV ↔ vacancy score with explanation (Python, FastAPI, pgvector)
- [ ] Cover-letter draft (local model via Ollama or an API provider)

## Tech stack

| Part | Technologies |
| --- | --- |
| Backend | Laravel 12, PHP 8.4, Horizon, Reverb |
| Frontend | Vue 3, TypeScript, Inertia, Tailwind |
| Data | PostgreSQL 17 + pgvector, Redis |
| AI service | Python, FastAPI, pgvector, Ollama |
| Quality | Pest, PHPStan, Pint, ESLint, pytest, GitHub Actions |

## Architecture

_Diagram coming with the AI service._

## Getting started

Requirements: PHP 8.4, Composer, Node 22+, Docker.

```bash
git clone https://github.com/clownshegi/jobpilot.git
cd jobpilot
cp .env.example .env
composer install
npm install
php artisan key:generate
docker compose up -d        # Postgres (pgvector) + Redis
php artisan migrate
composer run dev            # http://localhost:8000
```

Tests:

```bash
vendor/bin/pest
```

## Roadmap

- **v0.1** — tracker: CRUD, kanban, import by URL, realtime, PHPStan
- **v0.2** — AI service: embeddings, matching, cover letters, Laravel ↔ Python integration, one-command `docker compose up`

## License

MIT
