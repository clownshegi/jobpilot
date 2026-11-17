<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompanyRequest;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CompanyController extends Controller
{
    public function index(Request $request): Response
    {
        $companies = $request->user()->companies()
            ->withCount('vacancies')
            ->orderBy('name')
            ->get();

        return Inertia::render('companies/Index', [
            'companies' => CompanyResource::collection($companies),
        ]);
    }

    public function store(CompanyRequest $request): RedirectResponse
    {
        $request->user()->companies()->create($request->validated());

        return back();
    }

    public function update(CompanyRequest $request, Company $company): RedirectResponse
    {
        Gate::authorize('update', $company);

        $company->update($request->validated());

        return back();
    }

    public function destroy(Company $company): RedirectResponse
    {
        Gate::authorize('delete', $company);

        $company->delete();

        return to_route('companies.index');
    }
}
