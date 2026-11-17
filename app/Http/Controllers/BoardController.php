<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Http\Resources\JobApplicationResource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BoardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $applications = $request->user()->jobApplications()
            ->with('vacancy.company')
            ->orderBy('position')
            ->orderBy('job_applications.id')
            ->get()
            ->groupBy(fn ($application) => $application->status->value);

        $columns = array_map(fn (ApplicationStatus $status) => [
            'status' => $status->value,
            'label' => $status->label(),
            'applications' => JobApplicationResource::collection($applications->get($status->value, collect()))->resolve($request),
        ], ApplicationStatus::cases());

        return Inertia::render('Board', [
            'columns' => $columns,
        ]);
    }
}
