<?php
namespace App\Mail; use Illuminate\Bus\Queueable; use Illuminate\Mail\Mailable; use Illuminate\Queue\SerializesModels;
class CustomerPromotionMail extends Mailable {use Queueable,SerializesModels; public function __construct(public string $subjectLine, public string $messageText){} public function build(){return $this->subject($this->subjectLine)->view('emails.customer-promotion');}}
