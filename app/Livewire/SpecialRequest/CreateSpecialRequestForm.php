<?php

namespace App\Livewire\SpecialRequest;

use App\Actions\SpecialRequest\CreateSpecialRequest;
use App\Data\SpecialRequest\CreateSpecialRequestData;
use App\Filament\Pages\Concierge\SpecialRequests;
use App\Models\Region;
use App\Models\ScheduleTemplate;
use App\Models\Venue;
use App\Traits\ManagesBookingForms;
use Carbon\Carbon;
use Exception;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Support\RawJs;
use Filament\Widgets\Widget;
use Sentry;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;

/**
 * @property Form $form
 */
class CreateSpecialRequestForm extends Widget implements HasForms
{
    use InteractsWithForms;
    use ManagesBookingForms;

    protected static ?string $pollingInterval = null;

    protected static string $view = 'livewire.special-request.create-special-request-form';

    public string $currency;

    public ?array $data = [];

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
                Select::make('venue')
                    ->prefixIcon('heroicon-m-building-storefront')
                    ->options(
                        fn () => Venue::available()
                            ->where('region', session('region', 'miami'))
                            ->pluck('name', 'id')
                    )
                    ->placeholder('Select Venue')
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
                            ->helperText('Please provide any additional information to pass on to the venue. Is this a birthday?  Are there any food restrictions?')
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
            ->options(fn () => collect()->range(6, 20)
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
                ->where('venue_id', $data['venue'])
                ->where('day_of_week', Carbon::parse($data['date'])->format('l'))
                ->where('start_time', $data['reservation_time'])
                ->where('party_size', 0)
                ->firstOrFail();
        } catch (Exception $e) {
            Sentry::captureException($e);

            Notification::make()
                ->title('Venue is not available at the selected time')
                ->warning()
                ->send();

            return;
        }
        $specialRequestData = new CreateSpecialRequestData(
            schedule_template_id: $scheduleTemplate->id,
            venue_id: $data['venue'],
            concierge_id: auth()->user()->concierge->id,
            booking_date: $data['date'],
            booking_time: $data['reservation_time'],
            party_size: $data['guest_count'],
            commission_requested_percentage: $data['commission_requested_percentage'],
            minimum_spend: $data['minimum_spend'],
            special_request: $data['special_request'],
            customer_first_name: $data['customer_first_name'],
            customer_last_name: $data['customer_last_name'],
            customer_phone: $data['customer_phone'],
            customer_email: $data['customer_email'],
        );

        CreateSpecialRequest::run($specialRequestData);

        $this->form->fill();

        $this->dispatch('special-request-created');

        Notification::make()
            ->title('Special Request Submitted to the Venue')
            ->success()
            ->send();

        $this->redirect(SpecialRequests::getUrl());
    }
}
