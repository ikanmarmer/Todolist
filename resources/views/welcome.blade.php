<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #333;
            background: #fff;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #06b6d4;
        }

        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #06b6d4;
        }

        .invoice-title {
            text-align: right;
            color: #374151;
        }

        .invoice-title h1 {
            font-size: 36px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 5px;
        }

        .invoice-number {
            color: #6b7280;
            font-size: 16px;
        }

        .company-info {
            margin-bottom: 30px;
        }

        .company-info h3 {
            color: #06b6d4;
            margin-bottom: 10px;
            font-size: 18px;
        }

        .company-details {
            color: #6b7280;
            line-height: 1.5;
        }

        .billing-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
        }

        .billing-info {
            width: 48%;
        }

        .billing-info h4 {
            color: #374151;
            margin-bottom: 10px;
            font-size: 16px;
            font-weight: bold;
        }

        .info-details {
            background: #f9fafb;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #06b6d4;
        }

        .invoice-details {
            margin-bottom: 40px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .detail-label {
            font-weight: bold;
            color: #374151;
        }

        .detail-value {
            color: #6b7280;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .items-table th {
            background: #06b6d4;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: bold;
        }

        .items-table td {
            padding: 15px;
            border-bottom: 1px solid #e5e7eb;
        }

        .items-table tr:last-child td {
            border-bottom: none;
        }

        .items-table tr:nth-child(even) {
            background: #f9fafb;
        }

        .plan-features {
            margin-top: 10px;
        }

        .feature-item {
            color: #059669;
            margin-bottom: 3px;
            font-size: 12px;
        }

        .feature-item:before {
            content: "✓ ";
            font-weight: bold;
            margin-right: 5px;
        }

        .totals-section {
            float: right;
            width: 300px;
            margin-bottom: 40px;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 10px;
            text-align: right;
        }

        .totals-table .label {
            text-align: left;
            font-weight: bold;
            color: #374151;
        }

        .totals-table .subtotal {
            border-top: 1px solid #e5e7eb;
        }

        .totals-table .tax {
            color: #6b7280;
        }

        .totals-table .total {
            border-top: 2px solid #06b6d4;
            font-size: 18px;
            font-weight: bold;
            color: #1f2937;
            background: #f0f9ff;
        }

        .amount {
            font-weight: bold;
        }

        .payment-status {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background: #d1fae5;
            border: 2px solid #10b981;
            border-radius: 8px;
        }

        .payment-status h3 {
            color: #065f46;
            font-size: 20px;
            margin-bottom: 5px;
        }

        .payment-status p {
            color: #047857;
        }

        .footer {
            text-align: center;
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 12px;
        }

        .footer strong {
            color: #374151;
        }

        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }

        @media print {
            .container {
                padding: 0;
            }

            body {
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo">
                📝 TodoApp Premium
            </div>
            <div class="invoice-title">
                <h1>INVOICE</h1>
                <div class="invoice-number"># {{ $invoice_number }}</div>
            </div>
        </div>

        <!-- Company Info -->
        <div class="company-info">
            <h3>{{ $company['name'] }}</h3>
            <div class="company-details">
                {{ $company['address'] }}<br>
                Phone: {{ $company['phone'] }}<br>
                Email: {{ $company['email'] }}
            </div>
        </div>

        <!-- Billing Section -->
        <div class="billing-section">
            <div class="billing-info">
                <h4>Bill To:</h4>
                <div class="info-details">
                    <strong>{{ $user_name }}</strong><br>
                    {{ $user_email }}<br>
                    @if($user_phone != '-')
                        Phone: {{ $user_phone }}
                    @endif
                </div>
            </div>

            <div class="billing-info">
                <h4>Invoice Details:</h4>
                <div class="info-details">
                    <div class="detail-row">
                        <span class="detail-label">Invoice Date:</span>
                        <span class="detail-value">{{ $date }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Due Date:</span>
                        <span class="detail-value">{{ $due_date }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Order ID:</span>
                        <span class="detail-value">#{{ $order_id }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Payment Method:</span>
                        <span class="detail-value">{{ $payment_method }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 50%">Description</th>
                    <th style="width: 15%; text-align: center">Qty</th>
                    <th style="width: 20%; text-align: right">Unit Price</th>
                    <th style="width: 15%; text-align: right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>{{ $plan_name }} Subscription</strong><br>
                        <span style="color: #6b7280; font-size: 12px;">{{ $plan_description }}</span>

                        @if($plan_features)
                        <div class="plan-features">
                            @foreach($plan_features as $feature)
                            <div class="feature-item">{{ $feature }}</div>
                            @endforeach
                        </div>
                        @endif
                    </td>
                    <td style="text-align: center">1</td>
                    <td style="text-align: right" class="amount">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                    <td style="text-align: right" class="amount">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Totals Section -->
        <div class="totals-section">
            <table class="totals-table">
                <tr class="subtotal">
                    <td class="label">Subtotal:</td>
                    <td class="amount">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                </tr>
                <tr class="tax">
                    <td class="label">PPN (11%):</td>
                    <td class="amount">Rp {{ number_format($tax, 0, ',', '.') }}</td>
                </tr>
                <tr class="total">
                    <td class="label">TOTAL:</td>
                    <td class="amount">Rp {{ number_format($total, 0, ',', '.') }}</td>
                </tr>
            </table>
        </div>

        <div class="clearfix"></div>

        <!-- Payment Status -->
        <div class="payment-status">
            <h3>✅ PAYMENT COMPLETED</h3>
            <p>Thank you for your payment. Your premium subscription is now active!</p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>Thank you for choosing TodoApp Premium!</strong></p>
            <p>This is a computer-generated invoice. No signature required.</p>
            <p>For questions about this invoice, please contact us at {{ $company['email'] }}</p>
            <br>
            <p style="font-size: 10px; color: #9ca3af;">
                Generated on {{ now()->format('d F Y H:i:s') }} | Invoice ID: {{ $invoice_number }}
            </p>
        </div>
    </div>
</body>
</html>
