<?php

namespace App\Actions;

use App\Constants\BookingPercentages;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Action;

class ParseTaskRequestAction extends Action
{
    /**
     * Parse the boss's chat message using the OpenAI API to create Asana tasks
     * within the context of the PRIMA platform.
     */
    public function handle(string $requestMessage): ?array
    {
        $openaiApiKey = config('services.openai.api_key');

        Log::info('Starting task parsing', ['message' => $requestMessage]);

        $platformContext = 'You are an AI assistant for PRIMA, a premium hospitality platform that connects:
        - Vendors (clubs, restaurants, venues) who provide premium experiences
        - Concierges who facilitate bookings for clients
        - Partners who refer vendors and concierges
        - Customers who book through concierges

        Technical Stack:
        - Laravel 11 (PHP 8.3) for backend infrastructure
        - Livewire 3 for dynamic UI components and real-time features
        - FilamentPHP 3 for admin panel and CRUD operations
        - TailwindCSS for styling
        - MySQL database
        - Stripe for payment processing
        - Twilio for SMS communications
        - AWS/Digital Ocean for infrastructure

        Key Technical Components:
        - Full-stack TALL (TailwindCSS, Alpine.js, Laravel, Livewire)
        - FilamentPHP admin panel for vendor/concierge management
        - Resource management and CRUD operations
        - Real-time booking updates with Livewire
        - Custom FilamentPHP plugins for specific features
        - Laravel Actions for business logic
        - Laravel Data objects for type safety
        - Eloquent relationships and model observers
        - Queue system for async operations
        - Event-driven architecture
        - API integrations (Stripe, Twilio, etc.)

        The platform handles:
        - Prime bookings (standard commission model)
        - Non-prime bookings (vendor pays, customer books free)
        - Schedule management
        - Payment processing
        - Two-tier concierge referrals
        - Partner commission tracking
        - Tax calculations
        - Communications (SMS, email)

        Key business rules:
        - Prime bookings:
            - Venue: Typically 60% but varies per venue
            - Concierge: 10-15% based on tier level
            - Platform: '.BookingPercentages::PLATFORM_PERCENTAGE_CONCIERGE.'% concierge + '.BookingPercentages::PLATFORM_PERCENTAGE_VENUE.'% venue
        - Non-prime bookings:
            - Concierge: '.BookingPercentages::NON_PRIME_CONCIERGE_PERCENTAGE.'% of venue fee
            - Platform: Processing fee of '.BookingPercentages::NON_PRIME_PROCESSING_FEE_PERCENTAGE.'%
            - Venue: '.BookingPercentages::NON_PRIME_VENUE_PERCENTAGE.'% (includes processing fee)
        - Referral structure:
            - Level 1: '.BookingPercentages::PRIME_REFERRAL_LEVEL_1_PERCENTAGE.'% of platform remainder
            - Level 2: '.BookingPercentages::PRIME_REFERRAL_LEVEL_2_PERCENTAGE.'% of platform remainder

        Common task categories:
        - Vendor onboarding/management
        - Concierge network expansion
        - Payment/commission issues
        - Booking system features
        - Partner relationship management
        - Customer support
        - Technical infrastructure
        - Marketing initiatives
        - Frontend development (Livewire/Blade)
        - Backend development (Laravel)
        - Admin panel enhancements (FilamentPHP)
        - API integrations
        - Database optimization
        - UI/UX improvements
        - Testing/QA
        - DevOps/Infrastructure

        When handling technical tasks, consider:
        - Laravel best practices and conventions
        - FilamentPHP plugin architecture
        - Livewire component lifecycle
        - Database performance impacts
        - Frontend/backend separation
        - Code reusability
        - Testing requirements
        - Documentation needs
        - Development environment setup';

        $prompt = "You are an assistant that converts a boss's chat message into a structured Asana task.
        You should combine all context and modifications into a single coherent task.
        Use the PRIMA platform context to properly categorize and detail the task.
        Consider the technical stack and architecture when categorizing development tasks.
        Return the result as a single-line JSON string in the following format:
        {\"name\": \"Task Name\", \"notes\": \"Detailed description\", \"category\": \"Task Category\", \"technical_context\": \"Relevant technical details\"}

        The task name should be concise but descriptive.
        The notes should include all details and modifications mentioned.
        The category should match one of the common task categories.
        For technical tasks, include relevant framework-specific details.

        Platform Context:
        $platformContext

        Extract the task name, description, category, and technical context from the following message:
        \"$requestMessage\"";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$openaiApiKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an AI assistant specialized in the PRIMA platform operations and development using Laravel 11, Livewire 3, and FilamentPHP 3.',
                ],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.2,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $assistantReply = $data['choices'][0]['message']['content'] ?? null;

            Log::info('OpenAI API response received', [
                'status' => $response->status(),
                'response' => $assistantReply,
            ]);

            if ($assistantReply) {
                // Clean up the response by removing newlines and extra whitespace
                $cleanedReply = preg_replace('/\s+/', ' ', trim((string) $assistantReply));
                // Remove any JSON code block markers if present
                $cleanedReply = preg_replace('/```json\s*|\s*```/', '', (string) $cleanedReply);

                $parsed = json_decode((string) $cleanedReply, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    Log::info('Successfully parsed task', ['parsed_data' => $parsed]);

                    return $parsed;
                }

                Log::error('JSON parsing failed', [
                    'error' => json_last_error_msg(),
                    'response' => $assistantReply,
                    'cleaned_response' => $cleanedReply,
                ]);
            }
        } else {
            Log::error('OpenAI API request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }

        return null;
    }
}
