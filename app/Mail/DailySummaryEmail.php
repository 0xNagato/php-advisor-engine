<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DailySummaryEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $summary) {}

    public function build()
    {
        return $this->subject('PRIMA Daily Summary - '.$this->summary['date'])
            ->markdown('emails.daily-summary');
    }
}
