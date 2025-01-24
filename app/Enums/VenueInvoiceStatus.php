<?php

namespace App\Enums;

enum VenueInvoiceStatus: string
{
    case DRAFT = 'draft';
    case SENT = 'sent';
    case PENDING = 'pending';
    case PAID = 'paid';
    case VOID = 'void';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SENT => 'Sent',
            self::PENDING => 'Pending Payment',
            self::PAID => 'Paid',
            self::VOID => 'Void',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::SENT => 'info',
            self::PENDING => 'warning',
            self::PAID => 'success',
            self::VOID => 'danger',
        };
    }
}
