<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Data\VenueOnboardingData;
use App\Models\Region;
use App\Models\User;
use App\Models\VenueOnboarding as VenueOnboardingModel;
use App\Notifications\VenueAgreementCopy;
use App\Notifications\VenueOnboardingSubmitted;
use App\Traits\FormatsPhoneNumber;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
        'partner' => 'Partner',
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

    /** @var array<int, TemporaryUploadedFile|null> */
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

    /** @var array<int, string> */
    public array $venue_regions = [];

    /** @var array<int, array{value: string, label: string}> */
    public array $availableRegions = [];

    #[Validate('required|exists:users,id')]
    public ?string $partner_id = null;

    public function mount(): void
    {
        // Initialize available regions
        $this->availableRegions = Region::active()
            ->get()
            ->map(fn (Region $region) => [
                'value' => $region->id,
                'label' => $region->name,
            ])
            ->toArray();

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
            $this->venue_use_non_prime_incentive = array_fill(0, $this->venue_count, true);
            $this->venue_non_prime_per_diem = array_fill(0, $this->venue_count, 10.0);
            $this->logo_files = array_fill(0, $this->venue_count, null);
            $this->venue_regions = array_fill(0, $this->venue_count, 'miami');
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
            'venue_regions' => $this->venue_regions,
            'has_logos' => $this->has_logos,
            'agreement_accepted' => $this->agreement_accepted,
            'venue_prime_hours' => $this->venue_prime_hours,
            'venue_use_non_prime_incentive' => $this->venue_use_non_prime_incentive,
            'venue_non_prime_per_diem' => $this->venue_non_prime_per_diem,
            'send_agreement_copy' => $this->send_agreement_copy,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'partner_id' => $this->partner_id,
        ]);
    }

    public function updatedVenueCount(): void
    {
        if ($this->venue_count > count($this->venue_names)) {
            $this->venue_names = array_pad($this->venue_names, $this->venue_count, '');
            $this->venue_prime_hours = array_pad($this->venue_prime_hours, $this->venue_count, []);
            $this->venue_use_non_prime_incentive = array_pad($this->venue_use_non_prime_incentive, $this->venue_count, true);
            $this->venue_non_prime_per_diem = array_pad($this->venue_non_prime_per_diem, $this->venue_count, 10.0);
            $this->venue_regions = array_pad($this->venue_regions, $this->venue_count, 'miami');
        } else {
            $this->venue_names = array_slice($this->venue_names, 0, $this->venue_count);
            $this->venue_prime_hours = array_slice($this->venue_prime_hours, 0, $this->venue_count);
            $this->venue_use_non_prime_incentive = array_slice($this->venue_use_non_prime_incentive, 0, $this->venue_count);
            $this->venue_non_prime_per_diem = array_slice($this->venue_non_prime_per_diem, 0, $this->venue_count);
            $this->venue_regions = array_slice($this->venue_regions, 0, $this->venue_count);
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
                'partner_id' => $this->partner_id,
            ]);

            $onboarding = VenueOnboardingModel::create([
                ...$data->toArray(),
                'status' => 'submitted',
            ]);

            foreach ($this->venue_names as $index => $name) {
                $location = $onboarding->locations()->create([
                    'name' => $name,
                    'region' => $this->venue_regions[$index],
                    'prime_hours' => $this->venue_prime_hours[$index] ?? [],
                    'use_non_prime_incentive' => $this->venue_use_non_prime_incentive[$index] ?? false,
                    'non_prime_per_diem' => $this->venue_use_non_prime_incentive[$index] ?
                        $this->venue_non_prime_per_diem[$index] :
                        null,
                    'logo_path' => $this->has_logos ? $this->storeLogo($index, $name) : null,
                ]);
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
            'partner' => $this->validate([
                'partner_id' => 'required|exists:users,id',
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
        $partners = User::query()
            ->select(['id', 'first_name', 'last_name'])
            ->whereHas('roles', fn ($query) => $query->where('name', 'partner'))
            ->orderBy('first_name')
            ->get()
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => "{$user->first_name} {$user->last_name}",
            ]);

        return view('livewire.venue-onboarding', [
            'partners' => $partners,
        ])->layout('components.layouts.app', [
            'title' => $this->submitted ? 'Onboarding Submitted' : 'Venue Onboarding',
        ]);
    }

    public function resetForm(): void
    {
        Session::forget('venue_onboarding');
        $this->reset();
        $this->mount();
    }

    public function updatedHasLogos(bool $value): void
    {
        if (! $value) {
            $this->logo_files = array_fill(0, $this->venue_count, null);
        }
    }

    public function deleteUpload(array $content, int $index): void
    {
        if (blank($this->logo_files[$index])) {
            return;
        }

        $this->logo_files[$index] = null;
    }

    protected function storeLogo(int $index, string $venueName): ?string
    {
        if (blank($this->logo_files[$index])) {
            return null;
        }

        try {
            $file = $this->logo_files[$index];
            $region = Str::slug($this->venue_regions[$index]);
            $venue = Str::slug($venueName);
            $company = Str::slug($this->company_name);
            $random = Str::random(6);

            $fileName = implode('-', [
                $region,
                $venue,
                $company,
                $random,
            ]).'.'.$file->getClientOriginalExtension();

            $path = $file->storeAs(
                'onboarded_venues',
                $fileName,
                ['disk' => 'do']
            );

            Storage::disk('do')->setVisibility($path, 'public');

            return $path;
        } catch (Exception $e) {
            Log::error('Error storing file:', [
                'error' => $e->getMessage(),
                'file_info' => [
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ],
            ]);

            return null;
        }
    }
}
