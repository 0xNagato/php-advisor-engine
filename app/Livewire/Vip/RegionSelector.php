<?php

namespace App\Livewire\Vip;

use App\Models\Region;
use App\Models\VipCode;
use App\Services\VipCodeService;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Widgets\Widget;

/**
 * @property Form $form
 */
class RegionSelector extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'livewire.vip.region-selector';

    protected static ?string $pollingInterval = null;

    public ?array $data = [];

    public ?VipCode $code = null;

    public function mount(): void
    {
        $this->code = $this->getCodeFromURL();
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Select::make('region')
                ->hiddenLabel()
                ->live()
                ->placeholder('Select a region')
                ->options(Region::active()->orderBy('id')->pluck('name', 'id'))
                ->extraAttributes(['class' => 'text-sm font-normal'])
                ->afterStateUpdated(function (Get $get) {
                    session(['vip-region' => $get('region')]);
                    $this->dispatch('vip-region-changed', $get('region'));
                })
                ->selectablePlaceholder(false)
                ->searchable()
                ->default($this->code->concierge->user->region)
                ->visible(fn () => count(config('app.active_regions', [])) > 1),
        ])
            ->statePath('data')
            ->extraAttributes(['class' => 'inline-form']);
    }

    private function getCodeFromURL(): ?VipCode
    {
        $currentUrl = url()->current();
        $path = parse_url($currentUrl, PHP_URL_PATH) ?? '';
        $segments = explode('/', trim($path, '/'));
        $code = end($segments);

        return app(VipCodeService::class)->findByCode($code);
    }
}
