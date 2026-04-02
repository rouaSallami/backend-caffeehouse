<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Addon;
use App\Http\Resources\AddonResource;

class AddonController extends Controller
{
    public function index()
    {
        $addons = Addon::latest()->get();

        return response()->json(
            AddonResource::collection($addons)->resolve()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'image' => ['nullable', 'string', 'max:2048'],
            'available' => ['nullable', 'boolean'],
        ]);

        $addon = Addon::create([
            'name' => $validated['name'],
            'price' => $validated['price'],
            'image' => $validated['image'] ?? '/images/cookie-maison1.jpg',
            'available' => $validated['available'] ?? true,
        ]);

        return response()->json(
            (new AddonResource($addon))->resolve(),
            201
        );
    }

    public function update(Request $request, $id)
    {
        $addon = Addon::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'image' => ['nullable', 'string', 'max:2048'],
        ]);

        $addon->update([
            'name' => $validated['name'],
            'price' => $validated['price'],
            'image' => $validated['image'] ?? $addon->image,
        ]);

        return response()->json(
            (new AddonResource($addon->fresh()))->resolve()
        );
    }

    public function destroy($id)
    {
        $addon = Addon::findOrFail($id);
        $addon->delete();

        return response()->json([
            'message' => 'Addon supprimé avec succès'
        ]);
    }

    public function toggleAvailability($id)
    {
        $addon = Addon::findOrFail($id);

        $addon->update([
            'available' => !$addon->available,
        ]);

        return response()->json(
            (new AddonResource($addon->fresh()))->resolve()
        );
    }
}