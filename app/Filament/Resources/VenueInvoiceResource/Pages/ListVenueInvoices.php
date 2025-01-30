<?php

declare(strict_types=1);

namespace App\Filament\Resources\VenueInvoiceResource\Pages;

use App\Enums\VenueInvoiceStatus;
use App\Filament\Resources\VenueInvoiceResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ListRecords\Tab;

class ListVenueInvoices extends ListRecords
{
    protected static string $resource = VenueInvoiceResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge(static::getResource()::getEloquentQuery()->count()),
            'draft' => Tab::make('Draft')
                ->badge(static::getResource()::getEloquentQuery()->where('status', VenueInvoiceStatus::DRAFT)->count())
                ->modifyQueryUsing(fn ($query) => $query->where('status', VenueInvoiceStatus::DRAFT)),
            'sent' => Tab::make('Sent')
                ->badge(static::getResource()::getEloquentQuery()->where('status', VenueInvoiceStatus::SENT)->count())
                ->modifyQueryUsing(fn ($query) => $query->where('status', VenueInvoiceStatus::SENT)),
            'paid' => Tab::make('Paid')
                ->badge(static::getResource()::getEloquentQuery()->where('status', VenueInvoiceStatus::PAID)->count())
                ->modifyQueryUsing(fn ($query) => $query->where('status', VenueInvoiceStatus::PAID)),
            'void' => Tab::make('Void')
                ->badge(static::getResource()::getEloquentQuery()->where('status', VenueInvoiceStatus::VOID)->count())
                ->modifyQueryUsing(fn ($query) => $query->where('status', VenueInvoiceStatus::VOID)),
        ];
    }
}
