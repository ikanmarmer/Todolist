<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\Payment;
use App\Models\User;
use Midtrans\Config;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\Invoice;
use App\Models\Plan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;


class PaymentController extends Controller
{
    public function __construct()
    {
        // Configure Midtrans
        Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        Config::$clientKey = env('MIDTRANS_CLIENT_KEY');
        Config::$isProduction = env('MIDTRANS_IS_PRODUCTION', false);
        Config::$isSanitized = env('MIDTRANS_IS_SANITIZED', true);
        Config::$is3ds = env('MIDTRANS_IS_3DS', true);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
        $payments = Payment::whereHas('order', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->with(['order.plan'])->orderBy('created_at', 'desc')->get();

        return response()->json($payments);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = Auth::user();
            $validator = Validator::make($request->all(), [
                'order_id' => 'required|exists:orders,id'
            ]);

             if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $order = $user->orders()->with('plan')->findOrFail($request->order_id);

            if ($order->status !== 'pending') {
                return response()->json(['error' => 'Order is not in pending status'], 400);
            }

            $payment = Payment::where('order_id', $order->id)->first();
            if ($payment) {
                return response()->json(['error' => 'Payment already exists for this order'], 400);
            }

            // Check if user has reached task limit for current plan
            $currentPlan = $user->plan;
            $tasksCount = $user->tasks()->count();

            if ($tasksCount >= $currentPlan->tasks_limit) {
                return response()->json([
                    'error' => 'Task limit reached',
                    'message' => 'You have reached the maximum task limit for your current plan',
                    'current_plan' => $currentPlan,
                    'tasks_count' => $tasksCount,
                    'limit' => $currentPlan->tasks_limit
                ], 400);
            }

            $newPayment = Payment::create([
                'order_id' => $order->id,
                'paid_at' => now()->toDateTimeString(),
                'transaction_status' => 'pending',
            ]);

            $midtransPayload = [
                'transaction_details' => [
                    'order_id' => $newPayment->id . '-' . time(),
                    'gross_amount' => (int) $order->amount,
                ],
                'customer_details' => [
                    'first_name' => $user->name,
                    'last_name' => '',
                    'email' => $user->email,
                    'phone' => $user->phone ?? '',
                ],
                'item_details' => [
                    [
                        'id' => $order->plan->id,
                        'price' => (int) $order->plan->price,
                        'quantity' => 1,
                        'name' => $order->plan->name . ' Plan',
                        'brand' => 'TodoApp',
                        'category' => 'Subscription'
                    ]
                ],
                'callbacks' => [
                    'finish' => env('APP_URL') . '/payment/finish'
                ],
                'expiry' => [
                    'start_time' => date('Y-m-d H:i:s O'),
                    'unit' => 'minutes',
                    'duration' => 60
                ]
            ];

            $snapToken = \Midtrans\Snap::getSnapToken($midtransPayload);

            $newPayment->update(['snap_token' => $snapToken]);

            DB::commit();
            return response()->json([
                'message' => 'Payment created successfully',
                'payment' => $newPayment->load('order.plan')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Transaction failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = Auth::user();
        $payment = Payment::with(['order.plan'])->findOrFail($id);
        if ($payment->order->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        return response()->json($payment);
    }

    /**
     * Generate PDF Invoice
     */
    public function generateInvoice($order_id, $user, $plan)
    {
        $invoiceNumber = 'INV-' . strtoupper(uniqid()) . '-' . date('Ymd');

        // Calculate tax (11% PPN)
        $subtotal = $plan->price;
        $tax = $subtotal * 0.11;
        $total = $subtotal + $tax;

        $data = [
            'invoice_number' => $invoiceNumber,
            'date' => now()->format('d F Y'),
            'due_date' => now()->addDays(30)->format('d F Y'),
            'transaction_status' => 'Paid',
            'user_name' => $user->name,
            'user_email' => $user->email,
            'user_phone' => $user->phone ?? '-',
            'plan_name' => $plan->name,
            'plan_description' => $plan->description,
            'plan_features' => json_decode($plan->features, true),
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
            'order_id' => $order_id,
            'payment_method' => 'Online Payment',
            'company' => [
                'name' => 'TodoApp Premium',
                'address' => 'Jl. Teknologi No. 123, Jakarta',
                'phone' => '021-1234567',
                'email' => 'billing@todoapp.com'
            ]
        ];

        $pdf = PDF::loadView('invoice', $data);
        $pdf->setPaper('A4', 'portrait');

        // Ensure directory exists
        $directory = "invoices/{$user->email}";
        if (!Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory);
        }

        $fileName = "{$invoiceNumber}.pdf";
        $filePath = "{$directory}/{$fileName}";

        Storage::disk('public')->put($filePath, $pdf->output());

        $invoice = Invoice::create([
            'order_id' => $order_id,
            'invoice_number' => $invoiceNumber,
            'pdf_url' => $filePath,
            'amount' => $total,
            'tax_amount' => $tax,
            'subtotal' => $subtotal
        ]);

        return $invoice;
    }

    /**
     * Download Invoice PDF
     */
    public function downloadInvoice($order_id)
    {
        $user = Auth::user();
        $order = Order::where('id', $order_id)
                     ->where('user_id', $user->id)
                     ->with(['plan', 'invoice'])
                     ->firstOrFail();

        if (!$order->invoice) {
            return response()->json(['error' => 'Invoice not found'], 404);
        }

        $filePath = storage_path('app/public/' . $order->invoice->pdf_url);

        if (!file_exists($filePath)) {
            return response()->json(['error' => 'Invoice file not found'], 404);
        }

        return response()->download($filePath, $order->invoice->invoice_number . '.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Midtrans Callback Handler
     */
    public function callback(Request $request)
    {
        try {
            $orderId = explode('-', $request->order_id)[0];
            $payment = Payment::findOrFail($orderId);
            $order = $payment->order;
            $user = $order->user;
            $plan = $order->plan;

            if ($request->transaction_status === 'settlement' || $request->transaction_status === 'capture') {
                DB::beginTransaction();

                try {
                    // Update payment status
                    $payment->update([
                        'transaction_status' => 'success',
                        'paid_at' => now(),
                        'payment_method' => $request->payment_type ?? 'unknown',
                        'transaction_id' => $request->transaction_id ?? null
                    ]);

                    // Update order status
                    $order->update([
                        'status' => 'completed',
                        'completed_at' => now()
                    ]);

                    // Update user plan
                    $user->update([
                        'plan_id' => $plan->id,
                        'plan_expires_at' => now()->addMonth(),
                        'tasks_used' => 0 // Reset task count for new plan
                    ]);

                    // Generate invoice
                    $this->generateInvoice($order->id, $user, $plan);

                    DB::commit();

                    return response()->json([
                        'message' => 'Payment successful',
                        'payment' => $payment->load('order.plan')
                    ], 200);

                } catch (\Exception $e) {
                    DB::rollBack();
                    throw $e;
                }

            } elseif ($request->transaction_status === 'pending') {
                $payment->update([
                    'transaction_status' => 'pending'
                ]);

                return response()->json([
                    'message' => 'Payment pending'
                ], 200);

            } elseif (in_array($request->transaction_status, ['deny', 'expire', 'cancel'])) {
                $payment->update([
                    'transaction_status' => $request->transaction_status
                ]);

                $order->update([
                    'status' => 'cancelled'
                ]);

                return response()->json([
                    'message' => 'Payment ' . $request->transaction_status
                ], 200);
            }

            return response()->json([
                'message' => 'Payment status: '. $request->transaction_status
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Callback processing failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user task count and limit info
     */
    public function getUserTaskInfo()
    {
        $user = Auth::user();
        $currentPlan = $user->plan;
        $tasksCount = $user->tasks()->count();

        return response()->json([
            'current_count' => $tasksCount,
            'limit' => $currentPlan->tasks_limit,
            'plan' => $currentPlan,
            'percentage' => ($tasksCount / $currentPlan->tasks_limit) * 100,
            'remaining' => $currentPlan->tasks_limit - $tasksCount
        ]);
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
