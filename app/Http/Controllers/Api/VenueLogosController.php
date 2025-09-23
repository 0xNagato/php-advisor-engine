<?php

namespace App\Http\Controllers\Api;

use App\Enums\VenueStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Venue;
use App\OpenApi\Parameters\VenueLogosParameters;
use App\OpenApi\Responses\VenueLogosCacheClearResponse;
use App\OpenApi\Responses\VenueLogosListResponse;
use DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Vyuldashev\LaravelOpenApi\Attributes as OpenApi;
use Vyuldashev\LaravelOpenApi\Attributes\Response as OpenApiResponse;

#[OpenApi\PathItem]
class VenueLogosController extends Controller
{
    /**
     * Array of venue IDs to exclude from results
     */
    private array $excludedVenueIds = [73, 74, 71, 72];

    /**
     * Number of venues to show per region
     */
    private array $amountByRegion = [
        'miami' => 20,
        'los_angeles' => 10,
        'ibiza' => 10,
    ];

    /**
     * Get venue logos data for the web component.
     *
     * Returns venue data formatted for the venue-logos-scroll web component.
     * Gets top booked venues from last 30 days, fills with random venues if needed.
     */
    #[OpenApi\Operation(
        tags: ['Venues']
    )]
    #[OpenApi\Parameters(
        factory: VenueLogosParameters::class
    )]
    #[OpenApiResponse(factory: VenueLogosListResponse::class)]
    public function index(Request $request): JsonResponse
    {
        $cacheKey = 'venue-logos-data-api';
        $cacheDuration = $request->has('fresh') ? 0 : 1440; // 24 hours unless fresh requested

        $venues = Cache::remember($cacheKey, $cacheDuration, fn () => $this->getTopVenues());

        // Randomize and split venues equally between first and second row
        $totalVenues = collect($venues)->flatten()->shuffle();
        $halfCount = (int) ceil($totalVenues->count() / 2);

        $firstRow = $totalVenues->take($halfCount);
        $secondRow = $totalVenues->skip($halfCount);

        // Format for API response
        $responseData = [
            'first_row' => $firstRow->map(fn ($venue) => $this->formatVenueForWebComponent($venue))->values(),
            'second_row' => $secondRow->map(fn ($venue) => $this->formatVenueForWebComponent($venue))->values(),
            'total_venues' => $totalVenues->count(),
            'generated_at' => now()->toISOString(),
        ];

        return response()->json($responseData)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type');
    }

    /**
     * Get top venues based on booking activity
     */
    private function getTopVenues(): Collection
    {
        $allVenues = collect();

        foreach ($this->amountByRegion as $region => $amount) {
            // Get most booked venues in last 30 days for this region
            $bookedVenues = Booking::query()
                ->select('venues.id', 'venues.name', 'venues.logo_path')
                ->join('schedule_templates', 'bookings.schedule_template_id', '=', 'schedule_templates.id')
                ->join('venues', 'schedule_templates.venue_id', '=', 'venues.id')
                ->whereNotNull('bookings.confirmed_at')
                ->where('bookings.created_at', '>=', now()->subDays(30))
                ->where('venues.status', VenueStatus::ACTIVE)
                ->where('venues.region', $region)
                ->whereNotIn('venues.id', $this->excludedVenueIds)
                ->groupBy('venues.id', 'venues.name', 'venues.logo_path')
                ->orderByDesc(DB::raw('COUNT(bookings.id)'))
                ->limit($amount)
                ->get();

            // If we don't have enough booked venues, fill with random active venues
            if ($bookedVenues->count() < $amount) {
                // Get IDs of venues we already have
                $existingIds = $bookedVenues->pluck('id');

                // Combine existing IDs with excluded IDs
                $idsToExclude = $existingIds->merge($this->excludedVenueIds)->unique();

                // Get additional random active venues for this region
                $additionalVenues = Venue::query()
                    ->select('id', 'name', 'logo_path')
                    ->where('status', VenueStatus::ACTIVE)
                    ->where('region', $region)
                    ->whereNotNull('logo_path')
                    ->whereNotIn('id', $idsToExclude)
                    ->inRandomOrder()
                    ->limit($amount - $bookedVenues->count())
                    ->get();

                $bookedVenues = $bookedVenues->concat($additionalVenues);
            }

            $allVenues = $allVenues->concat($bookedVenues);
        }

        return $allVenues;
    }

    /**
     * Format venue data for the web component.
     */
    private function formatVenueForWebComponent($venue): array
    {
        return [
            'id' => $venue->id,
            'name' => $venue->name,
            'logo_path' => $venue->logo_path ? $this->getLogoUrl($venue->logo_path) : null,
        ];
    }

    /**
     * Get the full URL for a venue logo.
     */
    private function getLogoUrl(string $logoPath): string
    {
        // If it's already a full URL, return as-is
        if (str_starts_with($logoPath, 'http')) {
            return $logoPath;
        }

        // Otherwise, generate the full URL using Laravel's storage
        return Storage::disk('do')->url($logoPath);
    }

    /**
     * Clear the venue logos cache.
     */
    #[OpenApi\Operation(
        tags: ['Venues']
    )]
    #[OpenApiResponse(factory: VenueLogosCacheClearResponse::class)]
    public function clearCache(): JsonResponse
    {
        Cache::forget('venue-logos-data-api');
        // Also clear the component cache for consistency
        Cache::forget('top_venues_scroll_latest_march_21');

        return response()->json([
            'message' => 'Venue logos cache cleared successfully',
            'cleared_at' => now()->toISOString(),
        ]);
    }
}
