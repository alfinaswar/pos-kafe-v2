<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InventoryAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $critical, public array $expiring)
    {
    }

    public function build()
    {
        return $this->subject('Alert Inventory POS Kafe')->view('emails.inventory-alert');
    }
}
