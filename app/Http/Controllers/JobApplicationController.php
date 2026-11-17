<?php

namespace App\Http\Controllers;

use App\Actions\MoveJobApplication;
use App\Enums\ApplicationStatus;
use App\Http\Requests\MoveJobApplicationRequest;
use App\Http\Requests\UpdateJobApplicationRequest;
use App\Models\JobApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class JobApplicationController extends Controller
{
    public function move(MoveJobApplicationRequest $request, JobApplication $jobApplication, MoveJobApplication $moveJobApplication): RedirectResponse
    {
        Gate::authorize('update', $jobApplication);

        $moveJobApplication->handle(
            $jobApplication,
            ApplicationStatus::from($request->validated('status')),
            $request->validated('position'),
        );

        return back();
    }

    public function update(UpdateJobApplicationRequest $request, JobApplication $jobApplication): RedirectResponse
    {
        Gate::authorize('update', $jobApplication);

        $jobApplication->update($request->validated());

        return back();
    }
}
