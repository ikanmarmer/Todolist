<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactMessage extends Mailable
{
    use Queueable, SerializesModels;

    public $contactData;

    public function __construct($contactData)
    {
        $this->contactData = $contactData;
    }

    public function build()
    {
        return $this->from($this->contactData['email'], $this->contactData['name'])
                    ->subject('Contact Form: ' . $this->contactData['subject'])
                    ->view('emails.contact')
                    ->with('contactData', $this->contactData);
    }
}
