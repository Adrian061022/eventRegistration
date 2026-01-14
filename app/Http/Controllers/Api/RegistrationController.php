<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RegistrationController extends Controller
{
    /**
     * Register the authenticated user for an event.
     */
    public function register(Event $event)
    {
        $user = Auth::user();

        // Check if event is in the past
        if ($event->date->isPast()) {
            return response()->json(['message' => 'Cannot register for a past event.'], 422);
        }

        // Check if event is full
        if ($event->registrations()->count() >= $event->max_participants) {
            return response()->json(['message' => 'Event is full.'], 422);
        }

        // Check if user is already registered
        $existingRegistration = $user->registrations()->where('event_id', $event->id)->first();
        if ($existingRegistration) {
            return response()->json(['message' => 'You are already registered for this event.'], 409);
        }

        $registration = $user->registrations()->create(['event_id' => $event->id]);

        return response()->json([
            'message' => 'Successfully registered for the event.',
            'registration' => $registration
        ], 201);
    }

    /**
     * Unregister the authenticated user from an event.
     */
    public function unregister(Event $event)
    {
        $user = Auth::user();

        $registration = $user->registrations()->where('event_id', $event->id)->first();

        if (!$registration) {
            return response()->json(['message' => 'You are not registered for this event.'], 404);
        }

        $registration->delete();

        return response()->json(['message' => 'Successfully unregistered from the event.']);
    }

    /**
     * Allow an admin to remove a user from an event.
     */
    public function adminRemoveUser(Event $event, User $user)
    {
        if (!Auth::user()->is_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $registration = $user->registrations()->where('event_id', $event->id)->first();

        if (!$registration) {
            return response()->json(['message' => 'User is not registered for this event.'], 404);
        }

        $registration->delete();

        return response()->json(['message' => 'User has been removed from the event.']);
    }
}
