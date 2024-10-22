<?php

/** @noinspection PhpUnused */

namespace Database\Seeders;

use App\Constants\SmsTemplates;
use App\Models\SmsTemplate;
use Illuminate\Database\Seeder;

class SmsTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach (SmsTemplates::TEMPLATES as $key => $content) {
            SmsTemplate::query()->updateOrCreate(['key' => $key], ['content' => $content]);
        }
    }
}
