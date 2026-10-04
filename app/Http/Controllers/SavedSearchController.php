<?php

namespace App\Http\Controllers;

use App\Enums\PropertyType;
use App\Http\Requests\SavedSearchRequest;
use App\Http\Resources\SavedSearchResource;
use App\Models\Branch;
use App\Models\SavedSearch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SavedSearchController extends Controller
{
    public function index(Request $request): Response
    {
        $searches = $request->user()
            ->savedSearches()
            ->latest()
            ->get();

        return Inertia::render('SavedSearches/Index', [
            'savedSearches' => SavedSearchResource::collection($searches),
            'regions' => Branch::query()->distinct()->orderBy('region')->pluck('region'),
            'propertyTypes' => PropertyType::options(),
        ]);
    }

    public function store(SavedSearchRequest $request): RedirectResponse
    {
        $request->user()->savedSearches()->create(
            $request->validated(),
        );

        return redirect()->route('saved-searches.index');
    }

    public function destroy(Request $request, SavedSearch $savedSearch): RedirectResponse
    {
        if ($request->user()->cannot('delete', $savedSearch)) {
            abort(403);
        }

        $savedSearch->delete();

        return redirect()->route('saved-searches.index');
    }
}
