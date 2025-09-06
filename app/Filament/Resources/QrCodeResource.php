<?php

namespace App\Filament\Resources;

use App\Actions\QrCode\AssignQrCodeToConcierge;
use App\Filament\Resources\QrCodeResource\Pages\CreateQrCode;
use App\Filament\Resources\QrCodeResource\Pages\EditQrCode;
use App\Filament\Resources\QrCodeResource\Pages\ListQrCodes;
use App\Models\Concierge;
use App\Models\QrCode;
use AshAllenDesign\ShortURL\Models\ShortURLVisit;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextColumn\TextColumnSize;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class QrCodeResource extends Resource
{
    protected static ?string $model = QrCode::class;

    protected static ?string $navigationIcon = 'heroicon-o-qr-code';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?string $navigationLabel = 'QR Code Management';

    protected static ?int $navigationSort = 100;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasActiveRole('super_admin') ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Ensure only super_admin users can access this resource
        if (! auth()->user()?->hasActiveRole('super_admin')) {
            return $query->whereRaw('1 = 0'); // Return empty result set
        }

        return $query;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasActiveRole('super_admin') ?? false;
    }

    public static function canView(Model $record): bool
    {
        return auth()->user()?->hasActiveRole('super_admin') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasActiveRole('super_admin') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->hasActiveRole('super_admin') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->hasActiveRole('super_admin') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->hasActiveRole('super_admin') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('QR Code Details')
                    ->schema([
                        Grid::make()
                            ->schema([
                                TextInput::make('url_key')
                                    ->label('URL Key')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->helperText('This key appears in the short URL and on the QR code for tracking')
                                    ->disabled(fn (Model $record) => $record->exists()),
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true),
                            ]),
                        Select::make('concierge_id')
                            ->label('Assigned Concierge')
                            ->relationship('concierge', 'hotel_name', fn ($query) => $query->with('user'))
                            ->getOptionLabelFromRecordUsing(fn (Concierge $record) => "{$record->user->name} ({$record->hotel_name})")
                            ->searchable()
                            ->preload()
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                if (blank($state)) {
                                    return;
                                }

                                $conciergeId = (int) $state;
                                $qrCode = QrCode::query()->find($get('id'));

                                if ($qrCode) {
                                    $concierge = Concierge::query()->find($conciergeId);
                                    app(AssignQrCodeToConcierge::class)->handle($qrCode, $concierge);
                                    $set('assigned_at', now()->format('Y-m-d H:i:s'));
                                }
                            }),
                        DateTimePicker::make('assigned_at')
                            ->label('Assigned At')
                            ->readonly(),
                    ])
                    ->columns(1),

                Section::make('QR Code Preview')
                    ->schema([
                        ViewField::make('qr_code_preview')
                            ->label('')
                            ->view('filament.resources.bulk-qr-code-resource.qr-code-preview'),
                    ])
                    ->visibleOn('edit'),

                Section::make('Usage Statistics')
                    ->schema([
                        TextInput::make('scan_count')
                            ->label('Scan Count')
                            ->numeric()
                            ->readonly(),
                        DateTimePicker::make('last_scanned_at')
                            ->label('Last Scanned At')
                            ->readonly(),
                        Placeholder::make('short_url')
                            ->label('Short URL')
                            ->content(fn (QrCode $record): Htmlable => new HtmlString("<a href=\"{$record->shortUrl}\" target=\"_blank\">{$record->shortUrl}</a>")),
                    ])
                    ->visibleOn('edit')
                    ->collapsible(),

                Section::make('Notes')
                    ->schema([
                        Textarea::make('notes')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['concierge.user', 'shortUrlModel']))
            ->columns([
                TextColumn::make('url_key')
                    ->label('URL Key')
                    ->searchable()
                    ->sortable()
                    ->hidden()
                    ->size(TextColumnSize::ExtraSmall),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->size(TextColumnSize::ExtraSmall),
                TextColumn::make('concierge.hotel_name')
                    ->label('Assigned To')
                    ->html()
                    ->formatStateUsing(function ($state, QrCode $record) {
                        if ($record->concierge) {
                            return "{$record->concierge->user->name}<br><span class=\"text-xs text-gray-500\">{$state}</span>";
                        }

                        // Check if it redirects to invitation form
                        if ($record->shortUrlModel && str_contains($record->shortUrlModel->destination_url, 'qr.unassigned')) {
                            return '<span class="text-warning-600 dark:text-warning-400">Awaiting Assignment (Invitation)</span>';
                        }

                        return '<span class="text-gray-400">Not Assigned</span>';
                    })
                    ->searchable()
                    ->sortable()
                    ->size(TextColumnSize::ExtraSmall),
                TextColumn::make('scan_count')
                    ->label('Scans')
                    ->sortable()
                    ->size(TextColumnSize::ExtraSmall),
                TextColumn::make('last_scanned_at')
                    ->label('Last Scan')
                    ->placeholder('N/A')
                    ->dateTime()
                    ->sortable()
                    ->size(TextColumnSize::ExtraSmall),
                TextColumn::make('assigned_at')
                    ->dateTime()
                    ->sortable()
                    ->hidden(),
                ToggleColumn::make('is_active')
                    ->label('Active'),
                TextColumn::make('shortUrl')
                    ->label('Short URL')
                    ->html()
                    ->formatStateUsing(fn (string $state): string => "<a class=\"text-xs\" href=\"{$state}\" target=\"_blank\">".$state.'</a>')
                    ->tooltip(fn (QrCode $record): ?string => $record->shortUrlModel?->destination_url)
                    ->size(TextColumnSize::ExtraSmall),
            ])
            ->filters([
                SelectFilter::make('concierge_id')
                    ->label('Assigned Concierge')
                    ->relationship('concierge', 'hotel_name', fn ($query) => $query->with('user'))
                    ->getOptionLabelFromRecordUsing(fn (Concierge $record) => "{$record->user->name} ({$record->hotel_name})")
                    ->searchable()
                    ->preload(),
                Filter::make('is_assigned')
                    ->label('Assignment Status')
                    ->form([
                        Select::make('assigned')
                            ->options([
                                'yes' => 'Assigned to Concierge',
                                'no' => 'Not Assigned',
                            ])
                            ->placeholder('All QR Codes'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => match ($data['assigned'] ?? null) {
                        'yes' => $query->whereNotNull('concierge_id'),
                        'no' => $query->whereNull('concierge_id'),
                        default => $query,
                    }),
                TernaryFilter::make('is_active')
                    ->label('Active status')
                    ->placeholder('All QR codes')
                    ->trueLabel('Active QR codes only')
                    ->falseLabel('Inactive QR codes only')
                    ->default(true),
            ])
            ->actions([
                Action::make('view_qr')
                    ->label('QR Code')
                    ->icon('heroicon-m-qr-code')
                    ->url(fn (QrCode $record): string => asset('storage/'.$record->qr_code_path))
                    ->openUrlInNewTab(),
                Action::make('visit_stats')
                    ->label('Stats')
                    ->icon('heroicon-m-chart-bar')
                    ->modalContent(fn (QrCode $record): HtmlString => new HtmlString(static::renderVisitStats($record)))
                    ->modalSubmitAction(false),
                Action::make('assign')
                    ->label('Assign')
                    ->icon('heroicon-m-user-plus')
                    ->form([
                        Select::make('concierge_id')
                            ->label('Concierge')
                            ->options(Concierge::with('user')->get()->mapWithKeys(fn ($concierge) => [$concierge->id => "{$concierge->user->name} ({$concierge->hotel_name})"]))
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (QrCode $record, array $data): void {
                        $concierge = Concierge::with('user')->find($data['concierge_id']);
                        if ($concierge) {
                            app(AssignQrCodeToConcierge::class)->handle($record, $concierge);
                            Notification::make()
                                ->title('QR code assigned to '.$concierge->user->name.' ('.$concierge->hotel_name.')')
                                ->success()
                                ->send();
                        }
                    }),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkAction::make('assign_bulk')
                    ->label('Assign to Concierge')
                    ->icon('heroicon-m-user-plus')
                    ->form([
                        Select::make('concierge_id')
                            ->label('Concierge')
                            ->options(Concierge::with('user')->get()->mapWithKeys(fn ($concierge) => [$concierge->id => "{$concierge->user->name} ({$concierge->hotel_name})"]))
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (BulkAction $action, array $data): void {
                        $concierge = Concierge::with('user')->find($data['concierge_id']);
                        if (! $concierge) {
                            return;
                        }

                        $assignQrCode = app(AssignQrCodeToConcierge::class);
                        $count = 0;

                        foreach ($action->getRecords() as $record) {
                            $assignQrCode->handle($record, $concierge);
                            $count++;
                        }

                        Notification::make()
                            ->title("{$count} QR codes assigned to {$concierge->user->name} ({$concierge->hotel_name})")
                            ->success()
                            ->send();
                    }),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQrCodes::route('/'),
            'create' => CreateQrCode::route('/create'),
            'edit' => EditQrCode::route('/{record}/edit'),
        ];
    }

    protected static function renderVisitStats(QrCode $qrCode): string
    {
        if (! $qrCode->short_url_id) {
            return '<div class="p-4">No statistics available for this QR code.</div>';
        }

        $visits = ShortURLVisit::query()->where('short_url_id', $qrCode->short_url_id)
            ->latest()
            ->limit(50)
            ->get();

        if ($visits->isEmpty()) {
            return '<div class="p-4">No visits recorded for this QR code yet.</div>';
        }

        $html = '<div class="p-4">';
        $html .= '<h3 class="mb-4 text-lg font-semibold">Last 50 Visits</h3>';
        $html .= '<div class="overflow-auto" style="max-height: 400px;">';
        $html .= '<table class="min-w-full divide-y divide-gray-200">';
        $html .= '<thead class="bg-gray-50">';
        $html .= '<tr>';
        $html .= '<th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Visited At</th>';
        $html .= '<th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">IP</th>';
        $html .= '<th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Device</th>';
        $html .= '<th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Browser</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody class="bg-white divide-y divide-gray-200">';

        foreach ($visits as $visit) {
            $html .= '<tr>';
            $html .= "<td class=\"px-6 py-4 whitespace-nowrap text-sm\">{$visit->visited_at}</td>";
            $html .= "<td class=\"px-6 py-4 whitespace-nowrap text-sm\">{$visit->ip_address}</td>";
            $html .= "<td class=\"px-6 py-4 whitespace-nowrap text-sm\">{$visit->device_type}</td>";
            $html .= "<td class=\"px-6 py-4 whitespace-nowrap text-sm\">{$visit->browser} {$visit->browser_version}</td>";
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }
}
