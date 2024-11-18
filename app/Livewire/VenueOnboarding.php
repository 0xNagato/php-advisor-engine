<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Data\VenueOnboardingData;
use App\Models\User;
use App\Models\VenueOnboarding as VenueOnboardingModel;
use App\Notifications\VenueAgreementCopy;
use App\Notifications\VenueOnboardingSubmitted;
use App\Traits\FormatsPhoneNumber;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Notification;

class VenueOnboarding extends Component
{
    use FormatsPhoneNumber;
    use WithFileUploads;

    /** @var array<string,string> */
    protected array $steps = [
        'company' => 'Company',
        'venues' => 'Venues',
        'prime-hours' => 'Hours',
        'incentive' => 'Incentives',
        'agreement' => 'Agreement',
    ];

    public bool $submitted = false;

    public string $step = 'company';

    #[Validate('required|string|max:255')]
    public string $company_name = '';

    #[Validate('required|integer|min:1')]
    public int $venue_count = 1;

    /** @var array<int, string> */
    public array $venue_names = [];

    public bool $has_logos = false;

    /** @var array<int, ?TemporaryUploadedFile> */
    public array $logo_files = [];

    public bool $agreement_accepted = false;

    /** @var array<int, array<string, array<string, bool>>> */
    public array $venue_prime_hours = [];

    /** @var array<int, bool> */
    public array $venue_use_non_prime_incentive = [];

    /** @var array<int, ?float> */
    public array $venue_non_prime_per_diem = [];

    public bool $send_agreement_copy = false;

    #[Validate('required|string|max:255')]
    public string $first_name = '';

    #[Validate('required|string|max:255')]
    public string $last_name = '';

    #[Validate('required|email|max:255')]
    public string $email = '';

    #[Validate('required|string')]
    public string $phone = '';

    /** @var array<string> */
    public array $timeSlots = [];

    public int $current_venue_index = 0;

    public function mount(): void
    {
        // Load saved state from session if it exists
        if (Session::has('venue_onboarding')) {
            $savedState = Session::get('venue_onboarding');
            foreach ($savedState as $key => $value) {
                if (property_exists($this, $key)) {
                    if ($key === 'phone') {
                        // Format phone number when loading from session
                        $this->phone = $this->getInternationalFormattedPhoneNumber($value);
                    } else {
                        $this->$key = $value;
                    }
                }
            }
            // Explicitly set the step from session
            $this->step = $savedState['step'] ?? 'company';
        } else {
            $this->venue_names = array_fill(0, $this->venue_count, '');
            $this->venue_prime_hours = array_fill(0, $this->venue_count, []);
            $this->venue_use_non_prime_incentive = array_fill(0, $this->venue_count, false);
            $this->venue_non_prime_per_diem = array_fill(0, $this->venue_count, null);
            $this->logo_files = array_fill(0, $this->venue_count, null);
            $this->step = 'company';
        }

        // Generate time slots in 30-minute increments from 11 AM to 11 PM
        $this->timeSlots = collect()
            ->range(0, 48)
            ->map(function ($slot) {
                $hour = 11 + floor($slot / 2);
                $minutes = ($slot % 2) * 30;

                return sprintf('%02d:%02d:00', $hour, $minutes);
            })
            ->filter(function ($time) {
                $hour = (int) substr($time, 0, 2);

                return $hour >= 11 && $hour < 23;
            })
            ->values()
            ->toArray();
    }

    public function updated($property): void
    {
        if ($property === 'phone') {
            $this->phone = $this->getInternationalFormattedPhoneNumber($this->phone);
        }

        // Save state to session whenever a property is updated
        Session::put('venue_onboarding', [
            'step' => $this->step,
            'company_name' => $this->company_name,
            'venue_count' => $this->venue_count,
            'venue_names' => $this->venue_names,
            'has_logos' => $this->has_logos,
            'agreement_accepted' => $this->agreement_accepted,
            'venue_prime_hours' => $this->venue_prime_hours,
            'venue_use_non_prime_incentive' => $this->venue_use_non_prime_incentive,
            'venue_non_prime_per_diem' => $this->venue_non_prime_per_diem,
            'send_agreement_copy' => $this->send_agreement_copy,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone, // Phone is already formatted at this point
        ]);
    }

    public function updatedVenueCount(): void
    {
        if ($this->venue_count > count($this->venue_names)) {
            $this->venue_names = array_pad($this->venue_names, $this->venue_count, '');
            $this->venue_prime_hours = array_pad($this->venue_prime_hours, $this->venue_count, []);
            $this->venue_use_non_prime_incentive = array_pad($this->venue_use_non_prime_incentive, $this->venue_count, false);
            $this->venue_non_prime_per_diem = array_pad($this->venue_non_prime_per_diem, $this->venue_count, null);
        } else {
            $this->venue_names = array_slice($this->venue_names, 0, $this->venue_count);
            $this->venue_prime_hours = array_slice($this->venue_prime_hours, 0, $this->venue_count);
            $this->venue_use_non_prime_incentive = array_slice($this->venue_use_non_prime_incentive, 0, $this->venue_count);
            $this->venue_non_prime_per_diem = array_slice($this->venue_non_prime_per_diem, 0, $this->venue_count);
        }

        $this->logo_files = array_fill(0, $this->venue_count, null);
    }

    public function nextStep(): void
    {
        $this->validateStep();

        if ($this->step === 'prime-hours' || $this->step === 'incentive') {
            if ($this->current_venue_index < count($this->venue_names) - 1) {
                $this->current_venue_index++;

                return;
            }
        }

        $this->step = match ($this->step) {
            'company' => 'venues',
            'venues' => 'prime-hours',
            'prime-hours' => 'incentive',
            'incentive' => 'agreement',
            default => $this->step
        };

        $this->current_venue_index = 0;

        Session::put('venue_onboarding', array_merge(
            Session::get('venue_onboarding', []),
            ['step' => $this->step]
        ));
    }

    public function previousStep(): void
    {
        if (($this->step === 'prime-hours' || $this->step === 'incentive') && $this->current_venue_index > 0) {
            $this->current_venue_index--;

            return;
        }

        $steps = array_keys($this->steps);
        $currentIndex = array_search($this->step, $steps);

        if ($currentIndex > 0) {
            $this->step = $steps[$currentIndex - 1];

            // Set to last venue when going back to incentive or prime-hours step
            if ($this->step === 'incentive' || $this->step === 'prime-hours') {
                $this->current_venue_index = count($this->venue_names) - 1;
            } else {
                $this->current_venue_index = 0;
            }
        }
    }

    public function submit(): void
    {
        $this->validateStep();

        DB::transaction(function (): void {
            $data = VenueOnboardingData::from([
                'company_name' => $this->company_name,
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->email,
                'phone' => $this->phone,
                'venue_count' => $this->venue_count,
                'venue_names' => $this->venue_names,
                'has_logos' => $this->has_logos,
                'logo_files' => null,
                'agreement_accepted' => $this->agreement_accepted,
                'send_agreement_copy' => $this->send_agreement_copy,
            ]);

            $onboarding = VenueOnboardingModel::create([
                ...$data->toArray(),
                'status' => 'submitted',
            ]);

            foreach ($this->venue_names as $index => $name) {
                $location = $onboarding->locations()->create([
                    'name' => $name,
                    'prime_hours' => $this->venue_prime_hours[$index] ?? [],
                    'use_non_prime_incentive' => $this->venue_use_non_prime_incentive[$index] ?? false,
                    'non_prime_per_diem' => $this->venue_use_non_prime_incentive[$index] ?
                        $this->venue_non_prime_per_diem[$index] :
                        null,
                ]);

                if ($this->has_logos && isset($this->logo_files[$index])) {
                    $path = $this->logo_files[$index]->store('venue-logos', 'public');
                    $location->update(['logo_path' => $path]);
                }
            }

            if ($this->send_agreement_copy) {
                Notification::route('mail', $this->email)
                    ->notify(new VenueAgreementCopy($onboarding));
            }

            User::query()->whereHas('roles', function (Builder $query) {
                $query->where('name', 'super_admin');
            })->each(function ($admin) use ($onboarding) {
                $admin->notify(new VenueOnboardingSubmitted($onboarding));
            });
        });

        // Clear the session after successful submission
        Session::forget('venue_onboarding');
        $this->submitted = true;
    }

    private function validateStep(): void
    {
        match ($this->step) {
            'company' => $this->validate([
                'company_name' => 'required|string|max:255',
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'phone' => [
                    'required',
                    'string',
                    function ($attribute, $value, $fail) {
                        $phone = phone($value, config('app.countries')[0]);
                        if (! $phone->isValid()) {
                            $fail('The phone number is invalid.');
                        }
                    },
                ],
            ]),
            'venues' => $this->validate([
                'venue_count' => 'required|integer|min:1',
                'venue_names.*' => [
                    'required',
                    'string',
                    'max:255',
                ],
                'has_logos' => 'required|boolean',
                'logo_files.*' => 'nullable|image|max:2048',
            ], [
                'venue_names.*' => 'Please enter a name for Venue :position',
            ]),
            'agreement' => $this->validate([
                'agreement_accepted' => 'required|accepted',
            ]),
            'prime-hours' => $this->validate([
                'venue_prime_hours' => 'present|array',
            ]),
            'incentive' => $this->validate([
                'venue_use_non_prime_incentive' => 'required|array',
                'venue_non_prime_per_diem' => 'nullable|array',
            ]),
            default => null,
        };
    }

    public function render(): View
    {
        return view('livewire.venue-onboarding')
            ->layout('components.layouts.app', [
                'title' => $this->submitted ? 'Onboarding Submitted' : 'Venue Onboarding',
            ]);
    }

    public function resetForm(): void
    {
        Session::forget('venue_onboarding');
        $this->reset();
        $this->mount();
    }
}
