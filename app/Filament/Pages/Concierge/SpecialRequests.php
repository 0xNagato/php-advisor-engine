<?php

namespace App\Filament\Pages\Concierge;

use App\Enums\SpecialRequestStatus;
use App\Events\SpecialRequestCreated;
use App\Models\Region;
use App\Models\Restaurant;
use App\Models\ScheduleTemplate;
use App\Models\SpecialRequest;
use App\Models\User;
use App\Traits\ManagesBookingForms;
use Carbon\Carbon;
use Exception;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\RawJs;
use libphonenumber\PhoneNumberType;
use Sentry;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;

/**
 * @property Form $form
 */
class SpecialRequests extends Page
{
    use ManagesBookingForms;

    protected static ?string $navigationIcon = 'polaris-bill-filled-icon';

    protected static string $view = 'filament.pages.concierge.special-request-form';

    protected static ?int $navigationSort = -2;

    protected static ?string $slug = 'concierge/special-requests';

    public string $currency;

    public ?array $data = [];

    public static function canAccess(): bool
    {
        /** @var User $user */
        $user = auth()->user();

        return $user->hasRole('concierge');
    }

    public function mount(): void
    {
        $region = Region::query()->find(session('region', 'miami'));
        $this->timezone = $region->timezone;
        $this->currency = $region->currency;

        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                ...$this->commonFormComponents(),
                Select::make('restaurant')
                    ->prefixIcon('heroicon-m-building-storefront')
                    ->options(
                        fn () => Restaurant::available()
                            ->where('region', session('region', 'miami'))
                            ->pluck('restaurant_name', 'id')
                    )
                    ->placeholder('Select Restaurant')
                    ->required()
                    ->live()
                    ->hiddenLabel()
                    ->searchable()
                    ->columnSpanFull()
                    ->selectablePlaceholder(false),
                Section::make('Commission/Spend')
                    ->schema([
                        TextInput::make('commission_requested_percentage')
                            ->label('Commission')
                            ->placeholder('Commission')
                            ->numeric()
                            ->suffix('%')
                            ->default(10)
                            ->maxValue(15)
                            ->required(),
                        TextInput::make('minimum_spend')
                            ->label('Minimum Spend')
                            ->placeholder('00.00')
                            ->numeric()
                            ->mask(RawJs::make('$money($input)'))
                            ->prefix(Region::query()->find(session('region', 'miami'))->currency_symbol)
                            ->stripCharacters(',')
                            ->required(),
                    ])
                    ->extraAttributes(['class' => 'inline-form'])
                    ->columns([
                        'default' => 2,
                    ]),
                Section::make('Customer Details')
                    ->schema([
                        TextInput::make('customer_first_name')
                            ->hiddenLabel()
                            ->placeholder('First Name')
                            ->required(),
                        TextInput::make('customer_last_name')
                            ->hiddenLabel()
                            ->placeholder('Last Name')
                            ->required(),
                        PhoneInput::make('customer_phone')
                            ->hiddenLabel()
                            ->onlyCountries(config('app.countries'))
                            ->displayNumberFormat(PhoneInputNumberType::E164)
                            ->disallowDropdown()
                            ->validateFor(
                                country: config('app.countries'),
                                type: PhoneNumberType::MOBILE,
                                lenient: true,
                            )
                            ->columnSpan(2)
                            ->required(),
                        TextInput::make('customer_email')
                            ->hiddenLabel()
                            ->email()
                            ->placeholder('Email Address (optional)')
                            ->autocomplete(false)
                            ->columnSpan(2),
                        Textarea::make('special_request')
                            ->hiddenLabel()
                            ->placeholder('Notes/Special Request')
                            ->helperText('Please provide any special requests or additional information that you would like the restaurant to know.')
                            ->columnSpan(2),
                    ])
                    ->extraAttributes(['class' => 'inline-form'])
                    ->columns([
                        'default' => 2,
                    ]),
            ])
            ->extraAttributes(['class' => 'inline-form'])
            ->columns([
                'default' => 2,
            ])
            ->statePath('data');
    }

    protected function getGuestCountInput(): Select
    {
        return Select::make('guest_count')
            ->prefixIcon('heroicon-m-users')
            ->options(fn () => collect()->range(6, 15)
                ->mapWithKeys(fn ($value) => [$value => $value.' Guests'])
            )
            ->placeholder('Party Size')
            ->live()
            ->hiddenLabel()
            ->columnSpan(1)
            ->required();
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        try {
            $scheduleTemplate = ScheduleTemplate::query()
                ->where('restaurant_id', $data['restaurant'])
                ->where('day_of_week', Carbon::parse($data['date'])->format('l'))
                ->where('start_time', $data['reservation_time'])
                ->where('party_size', 0)
                ->firstOrFail();
        } catch (Exception $e) {
            Sentry::captureException($e);

            Notification::make()
                ->title('Restaurant is not available at the selected time')
                ->warning()
                ->send();

            return;
        }

        $specialRequest = SpecialRequest::query()->create([
            'schedule_template_id' => $scheduleTemplate->id,
            'restaurant_id' => $data['restaurant'],
            'concierge_id' => auth()->id(),
            'booking_date' => $data['date'],
            'booking_time' => $data['reservation_time'],
            'party_size' => $data['guest_count'],
            'commission_requested_percentage' => $data['commission_requested_percentage'],
            'minimum_spend' => $data['minimum_spend'],
            'special_request' => $data['special_request'],
            'customer_first_name' => $data['customer_first_name'],
            'customer_last_name' => $data['customer_last_name'],
            'customer_phone' => $data['customer_phone'],
            'customer_email' => $data['customer_email'],
            'status' => SpecialRequestStatus::Pending,
        ]);

        SpecialRequestCreated::dispatch($specialRequest);

        Notification::make()
            ->title('Special Request Submitted to the Restaurant')
            ->success()
            ->send();
    }
}
