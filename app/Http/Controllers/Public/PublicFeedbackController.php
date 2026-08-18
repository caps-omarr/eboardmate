<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PublicFeedbackController extends Controller
{
    /**
     * Store and forward student evaluation survey responses to Google Sheets webhook.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            // Part I: Profile
            'name' => ['nullable', 'string', 'max:255'],
            'gender' => ['required', 'string', 'in:Male,Female,Prefer not to say'],
            'course' => ['required', 'string', 'max:255'],
            'year_level' => ['required', 'string', 'max:100'],
            'device_used' => ['required', 'string', 'max:100'],
            'internet_connection' => ['required', 'string', 'max:100'],

            // Part II: System Evaluation Ratings (Array of keys with values 1 to 5)
            'ratings' => ['required', 'array'],
            'ratings.*' => ['required', 'integer', 'min:1', 'max:5'],

            // Part III: Open-ended feedback
            'difficulties' => ['nullable', 'string', 'max:2000'],
            'suggestions' => ['nullable', 'string', 'max:2000'],
        ]);

        $webhookUrl = config('services.google_sheets.feedback_webhook_url') 
            ?? env('GOOGLE_SHEETS_FEEDBACK_WEBHOOK_URL');

        // Forward to Google Sheets Webhook asynchronously if configured
        if ($webhookUrl) {
            try {
                $payload = array_merge($validated, [
                    'submitted_at' => now()->toDateTimeString(),
                    'ip_address' => $request->ip(),
                ]);

                Http::timeout(5)
                    ->withoutVerifying()
                    ->post($webhookUrl, $payload);
            } catch (\Throwable $e) {
                Log::error('Google Sheets feedback webhook submission failed: ' . $e->getMessage());
            }
        }

        return response()->json([
            'message' => 'Thank you! Your feedback has been submitted successfully.',
        ], 200);
    }
}
