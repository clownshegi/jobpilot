<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Http\Resources\VacancyResource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $counts = $user->jobApplications()
            ->toBase()
            ->selectRaw('job_applications.status, count(*) as total')
            ->groupBy('job_applications.status')
            ->pluck('total', 'status');

        return Inertia::render('Dashboard', [
            'stats' => array_map(fn (ApplicationStatus $status) => [
                'status' => $status->value,
                'label' => $status->label(),
                'count' => (int) ($counts[$status->value] ?? 0),
            ], ApplicationStatus::cases()),
            'recentVacancies' => VacancyResource::collection(
                $user->vacancies()->with(['company', 'application'])->latest()->limit(5)->get(),
            ),
        ]);
    }
}
