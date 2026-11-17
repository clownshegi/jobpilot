<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVacancyImportRequest;
use App\Http\Resources\VacancyImportResource;
use App\Jobs\ImportVacancyFromUrl;
use Illuminate\Http\RedirectResponse;

class VacancyImportController extends Controller
{
    /**
     * Queues the import and returns right away; progress arrives over the websocket.
     */
    public function store(StoreVacancyImportRequest $request): RedirectResponse
    {
        $import = $request->user()->vacancyImports()->create(['url' => $request->validated('url')]);

        ImportVacancyFromUrl::dispatch($import);

        return back()->with('vacancyImport', VacancyImportResource::make($import)->resolve());
    }
}
