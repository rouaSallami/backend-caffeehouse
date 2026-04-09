<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Addon;
use App\Http\Resources\AddonResource;
use Illuminate\Support\Facades\Storage;

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
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'available' => ['nullable', 'boolean'],
        ]);

        $imagePath = '/images/cookie-maison1.jpg';

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('addons', 'public');
            $imagePath = '/storage/' . $path;
        }

        $addon = Addon::create([
            'name' => $validated['name'],
            'price' => $validated['price'],
            'image' => $imagePath,
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
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $imagePath = $addon->image;

        if ($request->hasFile('image')) {
            if ($addon->image && str_starts_with($addon->image, '/storage/')) {
                $oldPath = str_replace('/storage/', '', $addon->image);

                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            $path = $request->file('image')->store('addons', 'public');
            $imagePath = '/storage/' . $path;
        }

        $addon->update([
            'name' => $validated['name'],
            'price' => $validated['price'],
            'image' => $imagePath,
        ]);

        return response()->json(
            (new AddonResource($addon->fresh()))->resolve()
        );
    }

    public function destroy($id)
    {
        $addon = Addon::findOrFail($id);

        if ($addon->image && str_starts_with($addon->image, '/storage/')) {
            $oldPath = str_replace('/storage/', '', $addon->image);

            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

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