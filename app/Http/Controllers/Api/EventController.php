<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Event::orderBy('date', 'desc')->paginate(15);
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
                'description' => 'nullable|string',
                'date' => 'required|date',
                'location' => 'required|string|max:255',
                'max_participants' => 'required|integer|min:1',
            ]);

            $event = Event::create($validated);

            return response()->json($event, 201);

        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Event $event)
    {
        // Eager load registrations and users
        return $event->load('registrations.user');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Event $event)
    {
        if (!Auth::user()->is_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'date' => 'sometimes|required|date',
                'location' => 'sometimes|required|string|max:255',
                'max_participants' => 'sometimes|required|integer|min:1',
            ]);

            $event->update($validated);

            return response()->json($event);

        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Event $event)
    {
        if (!Auth::user()->is_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $event->delete();

        return response()->json(['message' => 'Event successfully deleted.']);
    }

    /**
     * Display upcoming events.
     */
    public function upcoming()
    {
        return Event::where('date', '>=', now())
            ->orderBy('date', 'asc')
            ->paginate(15);
    }

    /**
     * Display past events.
     */
    public function past()
    {
        return Event::where('date', '<', now())
            ->orderBy('date', 'desc')
            ->paginate(15);
    }

    /**
     * Filter events by date range and location.
     */
    public function filter(Request $request)
    {
        $query = Event::query();

        if ($request->has('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        if ($request->has('location')) {
            $query->where('location', 'like', '%' . $request->location . '%');
        }

        return $query->orderBy('date', 'desc')->paginate(15);
    }
}
