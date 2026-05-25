<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Feedback::with('user')->latest()->get()
        );
    }

    public function update(Request $request, Feedback $feedback): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:new,read,resolved'],
        ]);

        $feedback->update($validated);

        return response()->json($feedback->load('user'));
    }

    public function destroy(Feedback $feedback): JsonResponse
    {
        $feedback->delete();

        return response()->json(null, 204);
    }
}
