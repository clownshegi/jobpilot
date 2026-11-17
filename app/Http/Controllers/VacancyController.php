<?php

namespace App\Http\Controllers;

use App\Actions\CreateVacancy;
use App\Actions\UpdateVacancy;
use App\Enums\ApplicationStatus;
use App\Enums\WorkMode;
use App\Events\BoardChanged;
use App\Http\Requests\StoreVacancyRequest;
use App\Http\Requests\VacancyRequest;
use App\Http\Resources\JobApplicationResource;
use App\Http\Resources\VacancyResource;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class VacancyController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(ApplicationStatus::class)],
        ]);

        $vacancies = $request->user()->vacancies()
            ->with(['company', 'application'])
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where(
                fn (Builder $query) => $query
                    ->whereLike('title', "%{$search}%")
                    ->orWhereHas('company', fn (Builder $query) => $query->whereLike('name', "%{$search}%")),
            ))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->whereRelation('application', 'status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('vacancies/Index', [
            'vacancies' => VacancyResource::collection($vacancies),
            'filters' => [
                'search' => $filters['search'] ?? '',
                'status' => $filters['status'] ?? '',
            ],
            'statuses' => ApplicationStatus::options(),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('vacancies/Create', $this->formOptions($request->user()));
    }

    public function store(StoreVacancyRequest $request, CreateVacancy $createVacancy): RedirectResponse
    {
        $vacancy = $createVacancy->handle($request->user(), $request->validated());

        return to_route('vacancies.show', $vacancy);
    }

    public function show(Vacancy $vacancy): Response
    {
        Gate::authorize('view', $vacancy);

        $vacancy->load(['company', 'application.statusChanges' => fn ($query) => $query->latest('id')]);

        return Inertia::render('vacancies/Show', [
            'vacancy' => VacancyResource::make($vacancy),
            'application' => JobApplicationResource::make($vacancy->application),
            'statuses' => ApplicationStatus::options(),
        ]);
    }

    public function edit(Request $request, Vacancy $vacancy): Response
    {
        Gate::authorize('update', $vacancy);

        return Inertia::render('vacancies/Edit', [
            'vacancy' => VacancyResource::make($vacancy->load('company')),
            ...$this->formOptions($request->user()),
        ]);
    }

    public function update(VacancyRequest $request, Vacancy $vacancy, UpdateVacancy $updateVacancy): RedirectResponse
    {
        Gate::authorize('update', $vacancy);

        $updateVacancy->handle($vacancy, $request->validated());
        BoardChanged::dispatch($vacancy->user_id);

        return to_route('vacancies.show', $vacancy);
    }

    public function destroy(Vacancy $vacancy): RedirectResponse
    {
        Gate::authorize('delete', $vacancy);

        $vacancy->delete();
        BoardChanged::dispatch($vacancy->user_id);

        return to_route('vacancies.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(User $user): array
    {
        return [
            'companyNames' => $user->companies()->orderBy('name')->pluck('name'),
            'statuses' => ApplicationStatus::options(),
            'workModes' => WorkMode::options(),
        ];
    }
}
