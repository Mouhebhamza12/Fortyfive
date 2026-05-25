<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FeedbackController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $user = $request->user();

        $feedback = Feedback::create([
            'user_id' => $user?->id,
            'name' => trim($validated['name']),
            'email' => isset($validated['email']) ? Str::lower(trim($validated['email'])) : null,
            'phone' => isset($validated['phone']) ? trim($validated['phone']) : null,
            'rating' => $validated['rating'] ?? null,
            'message' => trim($validated['message']),
            'status' => 'new',
        ]);

        return response()->json([
            'message' => 'Thank you for your feedback.',
            'feedback' => $feedback,
        ], 201);
    }
}
