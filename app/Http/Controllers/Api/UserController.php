<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (!Auth::user()->is_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        return User::paginate(15);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!Auth::user()->is_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:8',
                'phone' => 'nullable|string|max:20',
                'is_admin' => 'boolean',
            ]);

            $validated['password'] = Hash::make($validated['password']);
            $user = User::create($validated);

            return response()->json($user, 201);

        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        if (!Auth::user()->is_admin && Auth::id() !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        return $user->load('events');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        if (!Auth::user()->is_admin && Auth::id() !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'email' => ['sometimes', 'required', 'email', Rule::unique('users')->ignore($user->id)],
                'password' => 'sometimes|string|min:8',
                'phone' => 'nullable|string|max:20',
                'is_admin' => Auth::user()->is_admin ? 'boolean' : 'prohibited',
            ]);

            if (isset($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            }

            // Prevent non-admins from making themselves admin
            if (!Auth::user()->is_admin) {
                unset($validated['is_admin']);
            }

            $user->update($validated);

            return response()->json($user);

        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        if (!Auth::user()->is_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (Auth::id() === $user->id) {
            return response()->json(['message' => 'You cannot delete yourself.'], 403);
        }

                $user->delete();

        return response()->json(['message' => 'User successfully deleted.']);
    }

    /**
     * Get the authenticated User.
     */
    public function me()
    {
        return response()->json(Auth::user()->load('events'));
    }

    /**
     * Update the authenticated User.
     */
    public function updateMe(Request $request)
    {
        return $this->update($request, Auth::user());
    }

    /**
     * Log the user out (Invalidate the token).
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }
}
