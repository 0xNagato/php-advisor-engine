<?php

namespace App\Filament\Pages\Concierge;

use App\Filament\DateRangeFilterAction;
use App\Livewire\DateRangeFilterWidget;
use App\Models\Concierge;
use App\Models\VipCode;
use Carbon\Carbon;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Dashboard\Concerns\HasFiltersAction;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\On;

/**
 * @property Form $form
 */
class VipCodeManager extends Page
{
    use HasFiltersAction;

    protected static ?string $navigationIcon = 'heroicon-o-document-currency-dollar';

    protected static string $view = 'filament.pages.concierge.vip-code';

    protected static ?int $navigationSort = 3;

    public ?array $data = [];

    public static function canAccess(): bool
    {
        if (session()?->exists('simpleMode')) {
            return ! session('simpleMode');
        }

        return auth()->user()->hasRole(['concierge']);
    }

    public function mount(): void
    {
        $this->filters['startDate'] ??= now()->subDays(30)->format('Y-m-d');
        $this->filters['endDate'] ??= now()->format('Y-m-d');
        $this->form->fill();
    }

    public function getTitle(): Htmlable|string
    {
        $prefix = auth()->user()->hasRole('concierge') ? 'My ' : '';

        return $prefix.'VIP Codes';
    }

    public static function getNavigationLabel(): string
    {
        $prefix = auth()->user()->hasRole('concierge') ? 'My ' : '';

        return $prefix.'VIP Codes';
    }

    public function getHeaderWidgets(): array
    {
        return [
            DateRangeFilterWidget::make([
                'startDate' => $this->filters['startDate'],
                'endDate' => $this->filters['endDate'],
            ]),
        ];
    }

    public function getSubheading(): string|Htmlable|null
    {
        if (! isset($this->filters['startDate'], $this->filters['endDate'])) {
            return null;
        }

        $startDate = Carbon::parse($this->filters['startDate']);
        $endDate = Carbon::parse($this->filters['endDate']);

        $formattedStartDate = $startDate->format('M j');
        $formattedEndDate = $endDate->format('M j');

        return $formattedStartDate.' - '.$formattedEndDate;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('code')
                    ->label('VIP Code')
                    ->required()->minLength(4)->maxLength(12)
                    ->unique(table: VipCode::class)
                    ->validationMessages([
                        'required' => 'The VIP Code field is required.',
                        'unique' => 'The VIP Code has already been taken.',
                        'min' => 'The VIP Code field must be at least :min characters.',
                        'max' => 'The VIP Code field must be at least :max characters.',
                    ]),
                Select::make('conciergeId')
                    ->label('Concierge')
                    ->required()
                    ->options(Concierge::all()->pluck('hotel_name', 'id'))
                    ->searchable()->required()->columnSpan(2)
                    ->visible(fn (): bool => auth()->user()->hasRole('super_admin')),
                Actions::make([
                    Action::make('createCode')
                        ->label('Create VIP Code')
                        ->color('success')
                        ->action(function () {
                            $this->saveVipCode();
                        }),
                ])->columnSpanFull()->fullWidth(),
            ])
            ->extraAttributes(['class' => 'inline-form'])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            DateRangeFilterAction::make(),
        ];
    }

    public function saveVipCode(): void
    {
        $data = $this->form->getState();
        VipCode::query()->create([
            'code' => $data['code'],
            'concierge_id' => auth()->user()->concierge?->id ?? $data['conciergeId'],
        ]);
    }

    #[On('dateRangeUpdated')]
    public function updateDateRange(string $startDate, string $endDate): void
    {
        $this->filters['startDate'] = $startDate;
        $this->filters['endDate'] = $endDate;
    }
}
