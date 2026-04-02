<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CoffeeResource;
use App\Models\Coffee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CoffeeController extends Controller
{
    public function index()
    {
        $coffees = Coffee::with(['sizes', 'ingredients'])
            ->latest()
            ->get();

        return response()->json(
            CoffeeResource::collection($coffees)->resolve()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:255'],
            'image' => ['nullable', 'string', 'max:255'],
            'available' => ['nullable', 'boolean'],
            'isNew' => ['nullable', 'boolean'],
            'description' => ['required', 'string'],
            'ingredients' => ['nullable', 'array'],
            'ingredients.*' => ['nullable', 'string', 'max:255'],
            'sizes' => ['required', 'array', 'min:1'],
            'sizes.*.key' => ['nullable', 'string', 'max:50'],
            'sizes.*.label' => ['required', 'string', 'max:50'],
            'sizes.*.price' => ['required', 'numeric', 'min:0'],
        ]);

        DB::beginTransaction();

        try {
            $coffee = Coffee::create([
                'name' => $validated['name'],
                'category' => $validated['category'],
                'image' => $validated['image'] ?? null,
                'available' => $validated['available'] ?? true,
                'is_new' => $validated['isNew'] ?? false,
                'description' => $validated['description'],
            ]);

            if (!empty($validated['ingredients'])) {
                foreach ($validated['ingredients'] as $ingredient) {
                    if (filled($ingredient)) {
                        $coffee->ingredients()->create([
                            'name' => trim($ingredient),
                        ]);
                    }
                }
            }

            foreach ($validated['sizes'] as $size) {
                $coffee->sizes()->create([
                    'key' => $size['key'] ?? $size['label'],
                    'label' => $size['label'],
                    'price' => $size['price'],
                ]);
            }

            DB::commit();

            $coffee->load(['sizes', 'ingredients']);

            return response()->json([
                'message' => 'Café ajouté avec succès',
                'data' => (new CoffeeResource($coffee))->resolve(),
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors de l\'ajout du café',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        $coffee = Coffee::with(['sizes', 'ingredients'])->findOrFail($id);

        return response()->json([
            'data' => (new CoffeeResource($coffee))->resolve(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $coffee = Coffee::with(['sizes', 'ingredients'])->findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:255'],
            'image' => ['nullable', 'string', 'max:255'],
            'available' => ['nullable', 'boolean'],
            'isNew' => ['nullable', 'boolean'],
            'description' => ['required', 'string'],
            'ingredients' => ['nullable', 'array'],
            'ingredients.*' => ['nullable', 'string', 'max:255'],
            'sizes' => ['required', 'array', 'min:1'],
            'sizes.*.key' => ['nullable', 'string', 'max:50'],
            'sizes.*.label' => ['required', 'string', 'max:50'],
            'sizes.*.price' => ['required', 'numeric', 'min:0'],
        ]);

        DB::beginTransaction();

        try {
            $coffee->update([
                'name' => $validated['name'],
                'category' => $validated['category'],
                'image' => $validated['image'] ?? $coffee->image,
                'available' => $validated['available'] ?? $coffee->available,
                'is_new' => $validated['isNew'] ?? $coffee->is_new,
                'description' => $validated['description'],
            ]);

            $coffee->ingredients()->delete();
            if (!empty($validated['ingredients'])) {
                foreach ($validated['ingredients'] as $ingredient) {
                    if (filled($ingredient)) {
                        $coffee->ingredients()->create([
                            'name' => trim($ingredient),
                        ]);
                    }
                }
            }

            $coffee->sizes()->delete();
            foreach ($validated['sizes'] as $size) {
                $coffee->sizes()->create([
                    'key' => $size['key'] ?? $size['label'],
                    'label' => $size['label'],
                    'price' => $size['price'],
                ]);
            }

            DB::commit();

            $coffee->load(['sizes', 'ingredients']);

            return response()->json([
                'message' => 'Café modifié avec succès',
                'data' => (new CoffeeResource($coffee))->resolve(),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors de la modification du café',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $coffee = Coffee::with(['sizes', 'ingredients'])->findOrFail($id);

        DB::beginTransaction();

        try {
            $coffee->sizes()->delete();
            $coffee->ingredients()->delete();
            $coffee->delete();

            DB::commit();

            return response()->json([
                'message' => 'Café supprimé avec succès',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors de la suppression du café',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function toggleAvailability($id)
    {
        $coffee = Coffee::findOrFail($id);

        $coffee->update([
            'available' => !$coffee->available,
        ]);

        $coffee->load(['sizes', 'ingredients']);

        return response()->json([
            'message' => 'Disponibilité mise à jour',
            'data' => (new CoffeeResource($coffee))->resolve(),
        ]);
    }

    public function toggleNew($id)
    {
        $coffee = Coffee::findOrFail($id);

        $coffee->update([
            'is_new' => !$coffee->is_new,
        ]);

        $coffee->load(['sizes', 'ingredients']);

        return response()->json([
            'message' => 'Statut nouveauté mis à jour',
            'data' => (new CoffeeResource($coffee))->resolve(),
        ]);
    }
}