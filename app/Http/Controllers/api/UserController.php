<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // ✅ GET /users
    public function index()
    {
        return response()->json(
            User::select('id', 'name', 'email', 'phone', 'role', 'created_at')
                ->latest()
                ->get()
        );
    }

    // ✅ POST /users
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string',
        ]);

        $user = User::create([
            ...$validated,
        ]);

        return response()->json($user, 201);
    }

    // ✅ PUT /users/{id}
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string',
            'email' => "required|email|unique:users,email,$id",
            'phone' => 'required|string',
        ]);

        $user->update($validated);

        return response()->json($user);
    }

    // ✅ DELETE /users/{id}
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json([
            'message' => 'User deleted'
        ]);
    }

    public function toggleActive($id)
{
    $user = User::findOrFail($id);

    $user->active = !$user->active;
    $user->save();

    return response()->json($user);
}
}