<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Quote Request - Seller Notification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 30px;
        }
        .quote-id {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .section {
            margin-bottom: 25px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        .section h3 {
            margin-top: 0;
            color: #667eea;
            font-size: 18px;
        }
        .customer-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .info-item {
            background: white;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #e9ecef;
        }
        .info-label {
            font-weight: bold;
            color: #6c757d;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .info-value {
            color: #333;
            font-size: 14px;
        }
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .products-table th,
        .products-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        .products-table th {
            background-color: #667eea;
            color: white;
            font-weight: bold;
        }
        .products-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .total-amount {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin: 20px 0;
        }
        .action-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
            transition: transform 0.2s;
        }
        .action-button:hover {
            transform: translateY(-2px);
        }
        .message-box {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            color: #6c757d;
            font-size: 12px;
        }
        .seller-badge {
            background: linear-gradient(135deg, #fd79a8 0%, #fdcb6e 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 10px;
        }
        @media (max-width: 600px) {
            .customer-info {
                grid-template-columns: 1fr;
            }
            .products-table {
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="seller-badge">🛍️ SELLER NOTIFICATION</div>
            <div class="quote-id">Quote Request #{{ $quoteRequest->id }}</div>
            <p style="margin: 0; opacity: 0.9;">New quote request received for your products</p>
        </div>

        <div class="section">
            <h3>👤 Customer Information</h3>
            <div class="customer-info">
                <div class="info-item">
                    <div class="info-label">Customer Name</div>
                    <div class="info-value">{{ $quoteRequest->name }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Email Address</div>
                    <div class="info-value">{{ $quoteRequest->email }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Phone Number</div>
                    <div class="info-value">{{ $quoteRequest->phone ?? 'Not provided' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Company</div>
                    <div class="info-value">{{ $quoteRequest->company ?? 'Not provided' }}</div>
                </div>
            </div>
        </div>

        @if($quoteRequest->products && count($quoteRequest->products) > 0)
        <div class="section">
            <h3>🛒 Requested Products</h3>
            <table class="products-table">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($quoteRequest->products as $product)
                    <tr>
                        <td>{{ $product['name'] }}</td>
                        <td>{{ $product['quantity'] }}</td>
                        <td>₹{{ number_format($product['price'], 2) }}</td>
                        <td>₹{{ number_format($product['price'] * $product['quantity'], 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            
            @if($quoteRequest->total_amount)
            <div class="total-amount">
                💰 Total Estimated Amount: ₹{{ number_format($quoteRequest->total_amount, 2) }}
            </div>
            @endif
        </div>
        @endif

        @if($quoteRequest->message)
        <div class="section">
            <h3>💬 Customer Message</h3>
            <div class="message-box">
                <p style="margin: 0;">{{ $quoteRequest->message }}</p>
            </div>
        </div>
        @endif

        <div class="section">
            <h3>📊 Quote Details</h3>
            <div class="customer-info">
                <div class="info-item">
                    <div class="info-label">Quote Status</div>
                    <div class="info-value">
                        <span style="background-color: #ffc107; color: #212529; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;">
                            {{ ucfirst($quoteRequest->status) }}
                        </span>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Request Date</div>
                    <div class="info-value">{{ $quoteRequest->created_at->format('M d, Y \a\t h:i A') }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Total Products</div>
                    <div class="info-value">{{ $quoteRequest->total_products }} items</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Quote ID</div>
                    <div class="info-value">#{{ $quoteRequest->id }}</div>
                </div>
            </div>
        </div>

        <div style="text-align: center;">
            <a href="{{ $adminUrl }}" class="action-button">
                🔍 View & Process Quote
            </a>
        </div>

        <div style="background-color: #e3f2fd; padding: 15px; border-radius: 8px; margin: 20px 0;">
            <h4 style="margin-top: 0; color: #1976d2;">🚀 Next Steps for Seller:</h4>
            <ul style="margin: 0; padding-left: 20px;">
                <li>Review the customer requirements carefully</li>
                <li>Check product availability and pricing</li>
                <li>Prepare a detailed quote with final pricing</li>
                <li>Contact the customer within 24 hours</li>
                <li>Update quote status in admin panel</li>
            </ul>
        </div>

        <div class="footer">
            <p>This is an automated notification for sellers. Please do not reply to this email.</p>
            <p>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>