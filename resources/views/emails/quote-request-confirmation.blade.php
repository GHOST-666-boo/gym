<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quote Request Confirmation</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #10b981; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .product-item { background: white; margin: 10px 0; padding: 15px; border-radius: 5px; }
        .footer { text-align: center; padding: 20px; color: #666; }
        .highlight { background: #e0f2fe; padding: 15px; border-radius: 5px; margin: 20px 0; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f5f5f5; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Quote Request Confirmed!</h1>
            <div style="background: rgba(255,255,255,0.2); padding: 10px; border-radius: 8px; margin-top: 15px;">
                <h2 style="margin: 0; font-size: 24px;">Quote ID: #{{ $quoteRequest->id }}</h2>
                <p style="margin: 5px 0 0 0; opacity: 0.9;">Keep this ID for future reference</p>
            </div>
        </div>
        
        <div class="content">
            <div class="highlight">
                <h2>Thank you, {{ $quoteRequest->name }}!</h2>
                <p>We've received your quote request and our team will get back to you within 24 hours with a detailed quote.</p>
            </div>

            <h2>Your Request Summary</h2>
            <table>
                <tr>
                    <th>Quote ID:</th>
                    <td>#{{ $quoteRequest->id }}</td>
                </tr>
                <tr>
                    <th>Request Date:</th>
                    <td>{{ $quoteRequest->created_at->format('M j, Y g:i A') }}</td>
                </tr>
                <tr>
                    <th>Status:</th>
                    <td><span style="background: #fef3c7; color: #92400e; padding: 4px 8px; border-radius: 4px;">{{ ucfirst($quoteRequest->status) }}</span></td>
                </tr>
            </table>

            <h2>Requested Products ({{ count($quoteRequest->products) }})</h2>
            @foreach($quoteRequest->products as $product)
                <div class="product-item">
                    <h3>{{ $product['name'] }}</h3>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <p><strong>Quantity:</strong> {{ $product['quantity'] }}</p>
                            @if(isset($product['category']))
                                <p><strong>Category:</strong> {{ $product['category'] }}</p>
                            @endif
                        </div>
                        <div style="text-align: right;">
                            <p><strong>${{ number_format($product['total'], 2) }}</strong></p>
                        </div>
                    </div>
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
                <tr style="font-size: 18px; font-weight: bold; background: #f0f9ff;">
                    <th>Estimated Total:</th>
                    <td>${{ number_format($quoteRequest->total_amount, 2) }}</td>
                </tr>
            </table>

            @if($quoteRequest->message)
                <h2>Your Requirements</h2>
                <div class="product-item">
                    <p>{{ $quoteRequest->message }}</p>
                </div>
            @endif

            <div class="highlight">
                <h3>What happens next?</h3>
                <ul>
                    <li>Our team will review your requirements</li>
                    <li>We'll prepare a detailed quote with final pricing</li>
                    <li>You'll receive the quote within 24 hours</li>
                    <li>We'll include delivery options and installation services</li>
                </ul>
            </div>

            <div class="highlight">
                <h3>Need to make changes?</h3>
                <p>If you need to modify your request or have additional questions, please reply to this email or contact us directly.</p>
                <p><strong>Email:</strong> {{ config('mail.from.address') }}</p>
                @if(site_phone())
                    <p><strong>Phone:</strong> {{ site_phone() }}</p>
                @endif
            </div>
        </div>
        
        <div class="footer">
            <p>Thank you for choosing {{ site_name() }}</p>
            <p>We appreciate your business and look forward to serving you!</p>
        </div>
    </div>
</body>
</html>