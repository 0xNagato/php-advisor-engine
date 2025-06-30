<?php

namespace App\Livewire\Availability;

use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Widgets\Widget;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\On;

/**
 * @property Form $form
 */
class AdvanceFilters extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'livewire.availability.advance-filters';

    protected static ?string $pollingInterval = null;

    public ?array $data = [];

    public ?string $currentRegion = null;

    public function mount(): void
    {
        $this->currentRegion = session('vip-region', auth()->user()->region ?? null);
        $this->form->fill();
    }

    #[On('vip-region-changed')]
    public function regionChanged(): void
    {
        $this->currentRegion = session('vip-region', auth()->user()->region ?? null);
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        $isIbizaRegion = $this->currentRegion === 'ibiza';

        return $form->schema([
            Toggle::make('advanceFilters')
                ->label(fn () => new HtmlString('<span class="text-xs sm:text-sm">Advanced</span>'))
                ->live()
                ->default(fn () => session('advanceFilters', false))
                ->afterStateUpdated(function (Get $get) {
                    session(['advanceFilters' => $get('advanceFilters')]);
                    $this->dispatch('advanceToggled', $get('advanceFilters'));
                })
                ->extraAttributes(['class' => 'mb-0 pb-0 toggle-sm']),
            Toggle::make('formentera')
                ->label(fn () => new HtmlString('<span class="text-xs sm:text-sm">Formentera</span>'))
                ->live()
                ->visible(fn () => $this->currentRegion === 'ibiza')
                ->default(false)
                ->extraAttributes(['class' => 'mt-0 pt-0 toggle-sm'])
                ->afterStateUpdated(function ($state) {
                    if ($state) {
                        $this->dispatch('formentera-selected');
                    } else {
                        $this->dispatch('formentera-unselected');
                    }
                }),
        ])
            ->statePath('data')
            ->extraAttributes(['class' => 'inline-form']);
    }
}
