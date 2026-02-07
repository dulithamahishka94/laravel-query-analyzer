<?php

namespace GladeHQ\QueryLens\Mail;

use GladeHQ\QueryLens\Models\Alert;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AlertTriggered extends Mailable
{
    use Queueable, SerializesModels;

    public Alert $alert;
    public string $messageContent; // Renamed to avoid using reserved $message
    public array $context;

    public function __construct(Alert $alert, string $message, array $context)
    {
        $this->alert = $alert;
        $this->messageContent = $message;
        $this->context = $context;
    }

    public function build()
    {
        return $this
            ->subject("Query Analyzer Alert: {$this->alert->name}")
            ->markdown('query-lens::emails.alert');
    }
}
