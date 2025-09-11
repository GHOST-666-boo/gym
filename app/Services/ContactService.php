<?php

namespace App\Services;

use App\Mail\ContactFormMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ContactService
{
    /**
     * Handle contact form submission and send email notification.
     *
     * @param array $contactData
     * @return bool
     */
    public function handleContactSubmission(array $contactData): bool
    {
        try {
            // Log the contact form submission for audit purposes
            Log::info('Contact form submission received', [
                'name' => $contactData['name'],
                'email' => $contactData['email'],
                'message_length' => strlen($contactData['message'])
            ]);

            // Send email notification to admin
            $adminEmail = config('mail.admin_email', config('mail.from.address'));
            
            Mail::to($adminEmail)->send(new ContactFormMail($contactData));

            // Log successful email sending
            Log::info('Contact form email sent successfully', [
                'to' => $adminEmail,
                'from' => $contactData['email']
            ]);

            return true;
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Failed to send contact form email', [
                'error' => $e->getMessage(),
                'contact_data' => $contactData
            ]);

            return false;
        }
    }

    /**
     * Validate and sanitize contact form data.
     *
     * @param array $data
     * @return array
     */
    public function sanitizeContactData(array $data): array
    {
        return [
            'name' => $this->sanitizeString(trim($data['name'])),
            'email' => trim(strtolower($data['email'])),
            'message' => $this->sanitizeString(trim($data['message'])),
            'submitted_at' => now()->toDateTimeString(),
            'ip_address' => request()->ip()
        ];
    }

    /**
     * Sanitize a string by removing HTML tags and potentially harmful content.
     *
     * @param string $input
     * @return string
     */
    private function sanitizeString(string $input): string
    {
        // First remove script tags and their content
        $sanitized = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $input);
        
        // Remove any javascript: protocols
        $sanitized = preg_replace('/javascript:/i', '', $sanitized);
        
        // Remove any on* event handlers
        $sanitized = preg_replace('/\bon\w+\s*=\s*["\'][^"\']*["\']/i', '', $sanitized);
        
        // Remove HTML and PHP tags
        $sanitized = strip_tags($sanitized);
        
        return $sanitized;
    }
}