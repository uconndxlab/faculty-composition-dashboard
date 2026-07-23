<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InstitutionController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $institutions = Institution::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('unitid', 'like', '%' . $search . '%')
                        ->orWhere('state', 'like', '%' . $search . '%')
                        ->orWhere('sector', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('name')
            ->paginate(40)
            ->withQueryString();

        return view('institutions.index', compact('institutions', 'search'));
    }

    public function edit(Institution $institution): View
    {
        return view('institutions.edit', compact('institution'));
    }

    public function update(Request $request, Institution $institution): RedirectResponse
    {
        $validated = $request->validate([
            'unitid' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'sector' => ['nullable', 'string', 'max:255'],
            'public_private' => ['nullable', 'in:Public,Private'],
            'carnegie_classification' => ['nullable', 'string', 'max:255'],
        ]);

        $publicPrivate = $validated['public_private'] ?? null;
        $isPublic = match ($publicPrivate) {
            'Public' => true,
            'Private' => false,
            default => $this->inferIsPublicFromSector($validated['sector'] ?? null),
        };

        $institution->fill([
            'unitid' => $this->cleanNullable($validated['unitid'] ?? null),
            'name' => trim($validated['name']),
            'state' => $this->cleanNullable($validated['state'] ?? null),
            'sector' => $this->cleanNullable($validated['sector'] ?? null),
            'public_private' => $publicPrivate,
            'is_public' => $isPublic,
            'carnegie_classification' => $this->cleanNullable($validated['carnegie_classification'] ?? null),
            'is_uconn' => $request->boolean('is_uconn'),
            'is_aau_public' => $request->boolean('is_aau_public'),
        ]);

        $institution->save();

        return redirect()->route('institutions.index')->with('status', 'Institution updated successfully.');
    }

    public function updateAauPublic(Request $request, Institution $institution): JsonResponse
    {
        $validated = $request->validate([
            'is_aau_public' => ['required', 'boolean'],
        ]);

        $institution->is_aau_public = (bool) $validated['is_aau_public'];
        $institution->save();

        return response()->json([
            'ok' => true,
            'institution_id' => $institution->id,
            'is_aau_public' => $institution->is_aau_public,
        ]);
    }

    private function cleanNullable(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function inferIsPublicFromSector(?string $sector): ?bool
    {
        if ($sector === null) {
            return null;
        }

        $value = strtolower(trim($sector));

        if ($value === '') {
            return null;
        }

        if (str_contains($value, 'public')) {
            return true;
        }

        if (str_contains($value, 'private')) {
            return false;
        }

        return null;
    }
}
