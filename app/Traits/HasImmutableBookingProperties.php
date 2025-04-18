<?php

namespace App\Traits;

trait HasImmutableBookingProperties
{
    protected array $immutableProperties = [
        // 'schedule_template_id',
        // 'booking_at',
    ];

    public function setAttribute($key, $value)
    {
        if (in_array($key, $this->immutableProperties) && $this->exists) {
            return $this;
        }

        return parent::setAttribute($key, $value);
    }
}
