<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Mail\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    public function send(Request $request)
{
    $v = Validator::make($request->all(), [
        'name'    => 'required|string|max:255',
        'email'   => 'required|email|max:255',
        'subject' => 'required|string|max:255',
        'message' => 'required|string',
    ]);

    if ($v->fails()) {
        return response()->json([
            'message' => 'Validation Error',
            'errors' => $v->errors()
        ], 422);
    }

    try {
        // Send to your designated contact email
        Mail::to(env('MAIL_USERNAME'))->send(new ContactMessage($request->all()));

        return response()->json([
            'message' => 'Your message has been sent. Thank you!'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Failed to send message. Please try again later.'
        ], 500);
    }
}
}
