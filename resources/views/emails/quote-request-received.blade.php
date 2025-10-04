<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Quote Request</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #2563eb; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .product-item { background: white; margin: 10px 0; padding: 15px; border-radius: 5px; }
        .footer { text-align: center; padding: 20px; color: #666; }
        .btn { display: inline-block; padding: 12px 24px; background: #2563eb; color: white; text-decoration: none; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f5f5f5; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔔 New Quote Request</h1>
            <div style="background: rgba(255,255,255,0.2); padding: 15px; border-radius: 8px; margin-top: 15px;">
                <h2 style="margin: 0; font-size: 28px;">Quote ID: #{{ $quoteRequest->id }}</h2>
                <p style="margin: 5px 0 0 0; opacity: 0.9;">{{ site_name() }} - Admin Notification</p>
            </div>
        </div>
        
        <div class="content">
            <h2>Customer Information</h2>
            <table>
                <tr>
                    <th>Name:</th>
                    <td>{{ $quoteRequest->name }}</td>
                </tr>
                <tr>
                    <th>Email:</th>
                    <td>{{ $quoteRequest->email }}</td>
                </tr>
                @if($quoteRequest->phone)
                <tr>
                    <th>Phone:</th>
                    <td>{{ $quoteRequest->phone }}</td>
                </tr>
                @endif
                @if($quoteRequest->company)
                <tr>
                    <th>Company:</th>
                    <td>{{ $quoteRequest->company }}</td>
                </tr>
                @endif
                <tr>
                    <th>Request Date:</th>
                    <td>{{ $quoteRequest->created_at->format('M j, Y g:i A') }}</td>
                </tr>
            </table>

            <h2>Requested Products ({{ count($quoteRequest->products) }})</h2>
            @foreach($quoteRequest->products as $product)
                <div class="product-item">
                    <h3>{{ $product['name'] }}</h3>
                    <p><strong>Quantity:</strong> {{ $product['quantity'] }}</p>
                    <p><strong>Unit Price:</strong> ${{ number_format($product['price'], 2) }}</p>
                    <p><strong>Total:</strong> ${{ number_format($product['total'], 2) }}</p>
                    @if(isset($product['category']))
                        <p><strong>Category:</strong> {{ $product['category'] }}</p>
                    @endif
                </div>
            @endforeach

            <table>
                <tr>
                    <th>Total Products:</th>
                    <td>{{ count($quoteRequest->products) }}</td>
                </tr>
                <tr>
                    <th>Total Quantity:</th>
                    <td>{{ collect($quoteRequest->products)->sum('quantity') }}</td>
                </tr>
                <tr style="font-size: 18px; font-weight: bold;">
                    <th>Estimated Total:</th>
                    <td>${{ number_format($quoteRequest->total_amount, 2) }}</td>
                </tr>
            </table>

            @if($quoteRequest->message)
                <h2>Additional Requirements</h2>
                <div class="product-item">
                    <p>{{ $quoteRequest->message }}</p>
                </div>
            @endif

            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ $adminUrl }}" class="btn">View in Admin Panel</a>
            </div>
        </div>
        
        <div class="footer">
            <p>This is an automated notification from {{ site_name() }}</p>
            <p>Please respond to the customer within 24 hours for best service.</p>
        </div>
    </div>
</body>
</html>