<?php

use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\BookingPlatformServiceProvider;
use App\Providers\EventServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\FilamentRenderHookProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
    */

    'name' => env('APP_NAME', 'PRIMA'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    'asset_url' => env('ASSET_URL'),

    'booking_url' => env('BOOKING_URL', 'https://book.primaapp.com'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. We have gone
    | ahead and set this to a sensible default for you out of the box.
    |
    */

    'timezone' => 'UTC',

    'countries' => [
        // North America
        'US', 'CA', 'MX', 'GT', 'BZ', 'HN', 'SV', 'NI', 'CR', 'PA',

        // Caribbean
        'CU', 'HT', 'DO', 'PR', 'JM', 'BS', 'BB', 'TT',

        // South America
        'CO', 'VE', 'GY', 'SR', 'BR', 'EC', 'PE', 'BO', 'PY', 'CL', 'AR', 'UY',

        // Europe
        'GB', 'IE', 'FR', 'BE', 'NL', 'DE', 'CH', 'IT', 'ES', 'PT', 'AT', 'PL', 'CZ', 'SK',
        'HU', 'RO', 'BG', 'HR', 'SI', 'GR', 'AL', 'MK', 'ME', 'RS', 'BA', 'LU', 'DK', 'NO',
        'SE', 'FI', 'EE', 'LV', 'LT', 'BY', 'UA', 'MD',

        // Asia
        'RU', 'GE', 'AZ', 'AM', 'KZ', 'UZ', 'TM', 'KG', 'TJ', 'CN', 'MN', 'KP', 'KR', 'JP',
        'VN', 'LA', 'KH', 'TH', 'MM', 'MY', 'SG', 'ID', 'PH', 'BN', 'TL', 'NP', 'BT', 'BD',
        'IN', 'PK', 'AF', 'IR', 'IQ', 'SY', 'LB', 'JO', 'IL', 'SA', 'YE', 'OM', 'AE', 'QA',
        'BH', 'KW',

        // Africa
        'EG', 'LY', 'TN', 'DZ', 'MA', 'MR', 'ML', 'NE', 'TD', 'SD', 'ER', 'ET', 'DJ', 'SO',
        'KE', 'UG', 'RW', 'BI', 'TZ', 'SN', 'GM', 'GW', 'GN', 'SL', 'LR', 'CI', 'GH', 'TG',
        'BJ', 'NG', 'CM', 'GA', 'GQ', 'CG', 'CD', 'AO', 'ZM', 'MW', 'MZ', 'ZW', 'BW', 'NA',
        'ZA', 'LS', 'SZ',

        // Oceania
        'AU', 'NZ', 'PG', 'SB', 'VU', 'NC', 'FJ', 'WS', 'TO', 'TV', 'KI', 'MH', 'FM', 'PW',
    ],
    // 'countries' => ['US', 'CA'],

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
    */

    'locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available. You may change the value to correspond to any of
    | the language folders that are provided through your application.
    |
    */

    'fallback_locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Faker Locale
    |--------------------------------------------------------------------------
    |
    | This locale will be used by the Faker PHP library when generating fake
    | data for your database seeds. For example, this will be used to get
    | localized telephone numbers, street address information and more.
    |
    */

    'faker_locale' => 'en_US',

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => 'file',
        // 'store' => 'redis',
    ],

    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. Feel free to add your own services to
    | this array to grant expanded functionality to your applications.
    |
    */

    'providers' => ServiceProvider::defaultProviders()->merge([
        /*
         * Package Service Providers...
         */

        /*
         * Application Service Providers...
         */
        AppServiceProvider::class,
        AuthServiceProvider::class,
        // App\Providers\BroadcastServiceProvider::class,
        EventServiceProvider::class,
        HorizonServiceProvider::class,
        AdminPanelProvider::class,
        RouteServiceProvider::class,
        FilamentRenderHookProvider::class,
        BookingPlatformServiceProvider::class,
    ])->toArray(),

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | This array of class aliases will be registered when this application
    | is started. However, feel free to register as many as you wish as
    | the aliases are "lazy" loaded so they don't hinder performance.
    |
    */

    'aliases' => Facade::defaultAliases()->merge([
        // 'Example' => App\Facades\Example::class,
    ])->toArray(),

    'default_timezone' => env('LOCAL_ENV_TIMEZONE', 'America/New_York'),
    'default_region' => env('LOCAL_ENV_REGION', 'miami'),
    'no_tax' => env('NO_TAX', false),
    'active_regions' => ['miami', 'los_angeles', 'ibiza'],

    /*
    |--------------------------------------------------------------------------
    | Region Geolocation Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for mapping region codes and country codes to Prima regions
    |
    */
    'region_code_mapping' => [
        'FL' => 'miami',
        'CA' => 'los_angeles',
        'IB' => 'ibiza',
    ],
    'country_region_mapping' => [
        // North America
        'US' => 'miami',
        'CA' => 'los_angeles',
        'MX' => 'los_angeles',

        // Europe
        'ES' => 'ibiza',
        'PT' => 'ibiza',
        'FR' => 'ibiza',
        'IT' => 'ibiza',
        'DE' => 'ibiza',
        'GB' => 'ibiza',
        'IE' => 'ibiza',
    ],

    'dev_ip_address' => env('DEV_IP_ADDRESS'),
    'native_key' => env('APP_NATIVE_KEY'),
    'primary_domain' => env('PRIMARY_DOMAIN', env('APP_URL', 'https://primavip.co')),
    'platform_url' => env('PLATFORM_URL', '/platform'),
    'apple_app_store_url' => env('APPLE_APP_STORE_URL', 'https://apps.apple.com/us/app/prima-vip/id6504947227'),
    'google_play_store_url' => env('GOOGLE_PLAY_STORE_URL'),
    'house' => [
        'concierge_id' => env('HOUSE_CONCIERGE_ID', 1),
        'vip_codes' => ['HOME', 'DIRECT'],
    ],
    'venue_booking_notification_phones' => env('VENUE_BOOKING_NOTIFICATION_PHONES', '+17865147601,+19176644415'),
    'widget_cache_timeout_minutes' => (int) env('WIDGET_CACHE_TIMEOUT_MINUTES', 5),
    'override_venues' => env('OVERRIDE_VENUES', ''),
    'closure_venues' => env('CLOSURE_VENUES', '73,76,95,112,129'),
    'ibiza_top_tier_venues' => env('IBIZA_TOP_TIER_VENUES', '189,195'),
    'los_angeles_top_tier_venues' => env('LOS_ANGELES_TOP_TIER_VENUES', ''),
    'miami_top_tier_venues' => env('MIAMI_TOP_TIER_VENUES', ''),
    'allow_advanced_toggle' => env('ALLOW_ADVANCED_TOGGLE', false),
    'test_stripe_email' => env('TEST_STRIPE_EMAIL', 'test@primavip.co'),

    /*
    |--------------------------------------------------------------------------
    | Booking Status
    |--------------------------------------------------------------------------
    |
    | This value determines whether bookings are globally enabled or disabled.
    | When set to false, all booking attempts will be prevented and a custom
    | message will be displayed.
    |
    */

    'bookings_enabled' => env('BOOKINGS_ENABLED', true),
    'bookings_disabled_message' => env('BOOKINGS_DISABLED_MESSAGE',
        'Bookings are currently disabled while we are onboarding venues and concierges. We expect to be live by mid-November.'),

    /*
    |--------------------------------------------------------------------------
    | Booking Validations
    |--------------------------------------------------------------------------
    |
    | Configuration flags for various booking validation rules and checks
    |
    */
    'check_customer_has_non_prime_booking' => env('CHECK_CUSTOMER_HAS_NON_PRIME_BOOKING', false),

    /*
    |--------------------------------------------------------------------------
    | Venue Onboarding Settings
    |--------------------------------------------------------------------------
    |
    | Configuration flags for venue onboarding process
    |
    */
    'venue_onboarding_unique_phone' => env('VENUE_ONBOARDING_UNIQUE_PHONE', false),
    'public_venue_onboarding_enabled' => env('PUBLIC_VENUE_ONBOARDING_ENABLED', true),
    'venue_onboarding_steps' => env('VENUE_ONBOARDING_STEPS', 'company,venues,agreement'),

    /*
    |--------------------------------------------------------------------------
    | Venue display options
    |--------------------------------------------------------------------------
    */
    'show_venue_modals' => env('SHOW_VENUE_MODALS', default: false),

    /*
    |--------------------------------------------------------------------------
    | Platform Sync Settings
    |--------------------------------------------------------------------------
    */
    'simulate_platform_sync_success' => env('SIMULATE_PLATFORM_SYNC_SUCCESS', false),

    /*
    |--------------------------------------------------------------------------
    | Specialty Filter Configuration
    |--------------------------------------------------------------------------
    |
    | Define which regions should show the specialty filter in the availability calendar
    |
    */
    'specialty_filter_regions' => ['ibiza'],

    /*
    |--------------------------------------------------------------------------
    | Reservation Calendar Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for reservation calendar date limits
    |
    */
    'max_reservation_days' => (int) env('MAX_RESERVATION_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | App Login Customization
    |--------------------------------------------------------------------------
    |
    | These settings allow for dynamic customization of the app login screen.
    | You can set the background image URL and text color through environment
    | variables or use the default values provided.
    |
    */

    'login' => [
        'background_image' => env('LOGIN_BACKGROUND_IMAGE'),
        'text_color' => env('LOGIN_TEXT_COLOR'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Invoice Preview Mode
    |--------------------------------------------------------------------------
    |
    | This flag enables returning HTML instead of generating PDFs for invoices
    | during development. Set to true to enable HTML preview mode.
    |
    */

    'invoice_html_preview' => env('INVOICE_HTML_PREVIEW', false),

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Feature flags to control certain functionality in the application
    |
    */
    'features' => [
        'show_qr_code_print_button' => env('FEATURE_SHOW_QR_PRINT_BUTTON', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | GOD IDs
    |--------------------------------------------------------------------------
    |
    | List of user IDs that have full administrative (God mode) access.
    | These users bypass all restrictions and have complete system access.
    |
    */
    'god_ids' => [1, 2, 204],

    /*
    |--------------------------------------------------------------------------
    | VIP Code Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for VIP code behavior and session management.
    |
    */
    'vip' => [
        'fallback_code' => env('VIP_FALLBACK_CODE', 'ALEX'),
        'session_duration_hours' => env('VIP_SESSION_DURATION_HOURS', 24),
    ],

    /*
    |--------------------------------------------------------------------------
    | Regional SMS Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for region-specific SMS templates. Defines which regions
    | should use alternative SMS messaging for certain booking types.
    |
    */
    'regional_sms' => [
        'non_prime_regions' => explode(',', (string) env('REGIONAL_SMS_NON_PRIME_REGIONS', 'ibiza')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Risk Screening Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the suspicious booking detection system.
    |
    */
    'risk_screening_enabled' => env('RISK_SCREENING_ENABLED', true),
    'ai_screening_enabled' => env('AI_SCREENING_ENABLED', false),
    'ai_screening_threshold_soft' => (int) env('AI_SCREENING_THRESHOLD_SOFT', 30),
    'ai_screening_threshold_hard' => (int) env('AI_SCREENING_THRESHOLD_HARD', 70),
    'send_low_risk_bookings_to_slack' => env('SEND_LOW_RISK_BOOKINGS_TO_SLACK', false),
    'risk_monitoring_user_ids' => array_filter(array_map('intval', explode(',', env('RISK_MONITORING_USER_IDS', '')))),

];
