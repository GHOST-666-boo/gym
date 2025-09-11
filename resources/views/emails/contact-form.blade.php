<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Contact Form Submission</title>
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
        .email-container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            background-color: #2c3e50;
            color: white;
            padding: 20px;
            margin: -30px -30px 30px -30px;
            border-radius: 8px 8px 0 0;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .field-group {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-left: 4px solid #3498db;
            border-radius: 4px;
        }
        .field-label {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 1px;
        }
        .field-value {
            font-size: 16px;
            color: #34495e;
        }
        .message-content {
            background-color: #ffffff;
            padding: 20px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            font-size: 12px;
            color: #7f8c8d;
            text-align: center;
        }
        .metadata {
            background-color: #ecf0f1;
            padding: 15px;
            border-radius: 4px;
            margin-top: 20px;
            font-size: 12px;
            color: #7f8c8d;
        }
        .metadata strong {
            color: #2c3e50;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>🏋️ New Contact Form Submission</h1>
        </div>

        <div class="field-group">
            <div class="field-label">Customer Name</div>
            <div class="field-value">{{ $contactData['name'] }}</div>
        </div>

        <div class="field-group">
            <div class="field-label">Email Address</div>
            <div class="field-value">
                <a href="mailto:{{ $contactData['email'] }}">{{ $contactData['email'] }}</a>
            </div>
        </div>

        <div class="field-group">
            <div class="field-label">Message</div>
            <div class="message-content">{{ $contactData['message'] }}</div>
        </div>

        @if(isset($contactData['submitted_at']) || isset($contactData['ip_address']))
        <div class="metadata">
            @if(isset($contactData['submitted_at']))
                <strong>Submitted:</strong> {{ $contactData['submitted_at'] }}<br>
            @endif
            @if(isset($contactData['ip_address']))
                <strong>IP Address:</strong> {{ $contactData['ip_address'] }}
            @endif
        </div>
        @endif

        <div class="footer">
            <p>This email was automatically generated from the contact form on {{ config('app.name') }}.</p>
            <p>You can reply directly to this email to respond to the customer.</p>
        </div>
    </div>
</body>
</html>