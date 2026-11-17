# JobPilot

![CI](https://github.com/clownshegi/jobpilot/actions/workflows/ci.yml/badge.svg)

A job-search tracker with AI matching. I built it to automate my own job search:
vacancies, companies, applications and their statuses on a kanban board, plus a Python service
that compares my CV with a vacancy and explains what matches and what is missing.

> 🚧 Work in progress. See [Roadmap](#roadmap).

## Features

- [x] Vacancies, companies, applications with status history
- [x] Kanban board with drag and drop
- [x] Import a vacancy by link: a queued job (Horizon + Redis) reads the page's schema.org `JobPosting` data, with SSRF protection
- [x] Real-time updates over WebSockets (Reverb): import progress, boards in other tabs refresh themselves
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

_Full diagram coming with the AI service._ Design decisions and their trade-offs are in [docs/decisions.md](docs/decisions.md).

Importing a vacancy by link:

```
browser ──POST /vacancy-imports──▶ Laravel ──job──▶ Redis queue ──▶ Horizon worker
   ▲                                                                   │ fetch page, parse JSON-LD,
   │                                                                   │ create vacancy
   └──────────── WebSocket (Reverb): import status, board changed ◀────┘
```

## Getting started

Requirements: PHP 8.4, Composer, Node 22+, Docker.
Postgres, Redis, the Horizon queue worker and the Reverb WebSocket server all run in Docker,
so the queue works on Windows too (Horizon needs the `pcntl` extension, which Windows PHP lacks).

```bash
git clone https://github.com/clownshegi/jobpilot.git
cd jobpilot
cp .env.example .env
composer install
npm install
php artisan key:generate
docker compose up -d        # Postgres (pgvector), Redis, Horizon, Reverb
php artisan migrate --seed  # demo data: demo@jobpilot.test / password
composer run dev            # app + Vite at http://localhost:8000, queue dashboard at /horizon
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
