<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\Payment;
use Midtrans\Config;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;


class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
        $payments = Payment::whereHas('order', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->get();

        return response()->json($payments);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        Config::$clientKey = env('MIDTRANS_CLIENT_KEY');
        Config::$isProduction = env('MIDTRANS_IS_PRODUCTION', false);
        Config::$isSanitized = env('MIDTRANS_IS_SANITIZED', true);
        Config::$is3ds = env('MIDTRANS_IS_3DS', true);

        DB::beginTransaction();
        try {
            $user = Auth::user();
            $validator = Validator::make($request->all(), [
                'order_id' => 'required|exists:orders,id'
            ]);

             if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            $order = $user->orders()->findOrFail($request->order_id);
            if ($order->status !== 'pending') {
                return response()->json(['error' => 'Order is not in pending status'], 400);
            }
            $payment = Payment::where('order_id', $order->id)->first();
            if ($payment) {
                return response()->json(['error' => 'Payment already exists for this order'], 400);
            }
            $newPayment = Payment::create([
                'order_id' => $order->id,
                'paid_at' => now()->toDateTimeString(),
                'transaction_status' => 'pending',
            ]);
            $midtransPayload = [
                'transaction_details' => [
                    'order_id' => $newPayment->id,
                    'gross_amount' => $order->total_price,
                ],
                'customer_details' => [
                    'first_name' => $user->name,
                    'email' => $user->email,
                ],
            ];
            $snapToken = \Midtrans\Snap::getSnapToken($midtransPayload);

            $newPayment->update(['snap_token' => $snapToken]);

            DB::commit();
            return response()->json([
                'message' => 'Payment created successfully',
                'payment' => $newPayment
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Transaction failed',
                'message' => $e->getMessage()
            ], 500);


        }
    }

    /**C
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = Auth::user();
        $payment = Payment::findOrFail($id);
        if ($payment->order->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        return response()->json($payment);
    }

    public function generateInvoice($order_id, $user, $plan)
    {
        $invoiceNumber = 'INV-' . strtoupper(uniqid());
        $data = [
            'invoice_number' => $invoiceNumber,
            'date' => now()->format('d M Y'),
            'transaction_status' => 'success',
            'user_name' => $user->name,
            'user_email' => $user->email,
            'plan_price' => $plan->price,
            'plan_duration' => $plan->duration,
            'order_id' => $order_id,
            'plan_name' => $plan->name,
            'price' => $plan->price,

        ];

        $pdf = PDF::loadView('invoice', $data);
        Storage::disk('public')->put("invoices/{$user->email}/{$invoiceNumber}.pdf", $pdf->output());
        $invoice = Invoice::create([
            'order_id' => $order_id,
            'invoice_number' => $invoiceNumber,
            'pdf_url' => "invoices/{$user->email}/{$invoiceNumber}.pdf",
        ]);
        return $invoice;
    }

    public function callback(Request $request)
    {
        $orderId = explode('-', $request->order_id)[0];
        $order = Order::findOrFail($orderId);

        $user = $order->user;
        $plan = $user->plan;
        if ($request->transaction_status === 'settlement') {
            $payment =  Payment::where('order_id', $order->id)->first();
            if (!$payment) {
                return response()->json(['error' => 'Payment not found'], 404);
            }
            $payment->update([
                'transaction_status' => $request->transaction_status,
                'paid_at' => now(),
            ]);
            $order->update([
                'transaction_status' => 'success',
                'paid_at' => now(),
            ]);
            $order->update([
                'status' => 'completed',
            ]);
            $this->generateInvoice($order->id, $user, $plan);
            return response()->json(['message' => 'Payment successful', 'payment' => $payment ], 200);
        } else {
            return response()->json(['message' => 'Payment status: '. $request->transaction_status], 200);
        }
        }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
