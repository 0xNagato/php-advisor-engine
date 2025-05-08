<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GenerateVenueAgreement;
use App\Models\VenueOnboarding;
use App\Notifications\VenueAgreementCopy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VenueAgreementController extends Controller
{
    // Constructor removed - middleware is now applied at the route level

    public function show(Request $request, string $onboarding): View
    {
        try {
            // Decrypt the ID
            $onboardingId = Crypt::decrypt($onboarding);
            $onboardingModel = VenueOnboarding::findOrFail($onboardingId);
            
            return view('venue.agreement', [
                'onboarding' => $onboardingModel,
            ]);
        } catch (\Exception $e) {
            abort(404, 'The agreement could not be found.');
        }
    }

    /**
     * This method is kept for backward compatibility but should no longer be used
     */
    public function download(Request $request, string $onboarding): StreamedResponse
    {
        return $this->publicDownload($request, $onboarding);
    }
    
    /**
     * Public download endpoint that doesn't require signature verification
     */
    public function publicDownload(Request $request, string $onboarding): StreamedResponse
    {
        try {
            // Decrypt the ID
            $onboardingId = Crypt::decrypt($onboarding);
            $onboardingModel = VenueOnboarding::findOrFail($onboardingId);
            
            // Generate the agreement PDF
            $pdfContent = GenerateVenueAgreement::run($onboardingModel);

            // Stream the download to the client
            return response()->streamDownload(
                fn () => print($pdfContent),
                'prima-venue-agreement.pdf',
                ['Content-Type' => 'application/pdf']
            );
        } catch (\Exception $e) {
            // Log the error for debugging but don't expose details to the user
            \Illuminate\Support\Facades\Log::error('Error downloading venue agreement', [
                'encrypted_id' => $onboarding,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            abort(404, 'The agreement could not be found.');
        }
    }

    public function email(Request $request, string $onboarding): RedirectResponse
    {
        try {
            // Decrypt the ID
            $onboardingId = Crypt::decrypt($onboarding);
            $onboardingModel = VenueOnboarding::findOrFail($onboardingId);
            
            $request->validate([
                'email' => 'required|email',
            ]);
            
            // Send email with the agreement attached
            Notification::route('mail', $request->input('email'))
                ->notify(new VenueAgreementCopy($onboardingModel));

            return back()->with('success', 'Agreement has been sent to your email.');
        } catch (\Exception $e) {
            abort(404, 'The agreement could not be found.');
        }
    }
}