<?php

namespace App\Data;

use App\Enums\VipCodeTemplate;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Data;

class AffiliateBrandingData extends Data
{
    public function __construct(
        public ?string $brand_name = null,
        public ?string $description = null,
        public ?string $logo_url = null,
        public ?string $main_color = null,
        public ?string $secondary_color = null,
        public ?string $gradient_start = null,
        public ?string $gradient_end = null,
        public ?string $text_color = null,
        public ?string $redirect_url = null,
        #[WithCast(EnumCast::class)]
        public ?VipCodeTemplate $template = null,
        // Influencer metadata
        public ?string $influencer_name = null,
        public ?string $influencer_handle = null,
        public ?string $follower_count = null,
        public ?string $social_url = null,
    ) {}

    public static function fromDefaults(): self
    {
        return new self(
            brand_name: null,
            description: null,
            logo_url: null,
            main_color: null,
            secondary_color: null,
            gradient_start: null,
            gradient_end: null,
            text_color: null,
            redirect_url: null,
            template: VipCodeTemplate::AVAILABILITY_CALENDAR,
            influencer_name: null,
            influencer_handle: null,
            follower_count: null,
            social_url: null,
        );
    }

    public function toApiResponse(): array
    {
        return [
            'brand_name' => $this->brand_name,
            'description' => $this->description,
            'logo_url' => $this->logo_url,
            'main_color' => $this->main_color,
            'secondary_color' => $this->secondary_color,
            'gradient_start' => $this->gradient_start,
            'gradient_end' => $this->gradient_end,
            'text_color' => $this->text_color,
            'redirect_url' => $this->redirect_url,
            'template' => $this->template?->value,
            'influencer_name' => $this->influencer_name,
            'influencer_handle' => $this->influencer_handle,
            'follower_count' => $this->follower_count,
            'social_url' => $this->social_url,
        ];
    }

    /**
     * Check if any branding data is configured
     */
    public function hasBranding(): bool
    {
        return ! empty(array_filter([
            $this->brand_name,
            $this->description,
            $this->logo_url,
            $this->main_color,
            $this->secondary_color,
            $this->gradient_start,
            $this->gradient_end,
            $this->text_color,
            $this->redirect_url,
            $this->template?->value,
            $this->influencer_name,
            $this->influencer_handle,
            $this->follower_count,
            $this->social_url,
        ]));
    }

    /**
     * Get the template or default if not set
     */
    public function getTemplate(): VipCodeTemplate
    {
        return $this->template ?? VipCodeTemplate::AVAILABILITY_CALENDAR;
    }
}
