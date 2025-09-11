<?php

namespace Tests\Unit;

use App\Mail\ContactFormMail;
use App\Services\ContactService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $contactService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->contactService = new ContactService();
    }

    public function test_sanitize_contact_data_removes_html_tags()
    {
        $rawData = [
            'name' => '<script>alert("xss")</script>John Doe<b>Bold</b>',
            'email' => '<script>test@example.com</script>',
            'message' => '<p>This is a <strong>test</strong> message with <em>HTML</em> tags.</p>',
        ];

        $sanitizedData = $this->contactService->sanitizeContactData($rawData);

        $this->assertEquals('John DoeBold', $sanitizedData['name']);
        $this->assertEquals('<script>test@example.com</script>', $sanitizedData['email']); // Email is not sanitized for HTML, only trimmed and lowercased
        $this->assertEquals('This is a test message with HTML tags.', $sanitizedData['message']);
    }

    public function test_sanitize_contact_data_trims_whitespace()
    {
        $rawData = [
            'name' => '  John Doe  ',
            'email' => '  test@example.com  ',
            'message' => '  This is a test message.  ',
        ];

        $sanitizedData = $this->contactService->sanitizeContactData($rawData);

        $this->assertEquals('John Doe', $sanitizedData['name']);
        $this->assertEquals('test@example.com', $sanitizedData['email']);
        $this->assertEquals('This is a test message.', $sanitizedData['message']);
    }

    public function test_sanitize_contact_data_converts_email_to_lowercase()
    {
        $rawData = [
            'name' => 'John Doe',
            'email' => 'TEST@EXAMPLE.COM',
            'message' => 'This is a test message.',
        ];

        $sanitizedData = $this->contactService->sanitizeContactData($rawData);

        $this->assertEquals('test@example.com', $sanitizedData['email']);
    }

    public function test_sanitize_contact_data_adds_metadata()
    {
        $rawData = [
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'message' => 'This is a test message.',
        ];

        $sanitizedData = $this->contactService->sanitizeContactData($rawData);

        $this->assertArrayHasKey('submitted_at', $sanitizedData);
        $this->assertArrayHasKey('ip_address', $sanitizedData);
        $this->assertNotNull($sanitizedData['submitted_at']);
        $this->assertNotNull($sanitizedData['ip_address']);
    }

    public function test_handle_contact_submission_sends_email()
    {
        Mail::fake();

        $contactData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'I am interested in your gym equipment.',
            'submitted_at' => now()->toDateTimeString(),
            'ip_address' => '127.0.0.1',
        ];

        $result = $this->contactService->handleContactSubmission($contactData);

        $this->assertTrue($result);
        
        Mail::assertSent(ContactFormMail::class, function ($mail) use ($contactData) {
            return $mail->contactData['name'] === $contactData['name'] &&
                   $mail->contactData['email'] === $contactData['email'] &&
                   $mail->contactData['message'] === $contactData['message'];
        });
    }

    public function test_handle_contact_submission_returns_false_on_mail_failure()
    {
        // Mock Mail facade to throw exception
        Mail::shouldReceive('to')->andThrow(new \Exception('Mail server error'));

        $contactData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'Test message',
            'submitted_at' => now()->toDateTimeString(),
            'ip_address' => '127.0.0.1',
        ];

        $result = $this->contactService->handleContactSubmission($contactData);

        $this->assertFalse($result);
    }

    public function test_validate_contact_data_structure()
    {
        $contactData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'Test message',
        ];

        $sanitizedData = $this->contactService->sanitizeContactData($contactData);

        // Verify all required fields are present
        $this->assertArrayHasKey('name', $sanitizedData);
        $this->assertArrayHasKey('email', $sanitizedData);
        $this->assertArrayHasKey('message', $sanitizedData);
        $this->assertArrayHasKey('submitted_at', $sanitizedData);
        $this->assertArrayHasKey('ip_address', $sanitizedData);

        // Verify data types
        $this->assertIsString($sanitizedData['name']);
        $this->assertIsString($sanitizedData['email']);
        $this->assertIsString($sanitizedData['message']);
        $this->assertIsString($sanitizedData['submitted_at']);
        $this->assertIsString($sanitizedData['ip_address']);
    }

    public function test_sanitize_contact_data_handles_empty_values()
    {
        $rawData = [
            'name' => '',
            'email' => '',
            'message' => '',
        ];

        $sanitizedData = $this->contactService->sanitizeContactData($rawData);

        $this->assertEquals('', $sanitizedData['name']);
        $this->assertEquals('', $sanitizedData['email']);
        $this->assertEquals('', $sanitizedData['message']);
        $this->assertArrayHasKey('submitted_at', $sanitizedData);
        $this->assertArrayHasKey('ip_address', $sanitizedData);
    }

    public function test_sanitize_contact_data_handles_null_values()
    {
        $rawData = [
            'name' => null,
            'email' => null,
            'message' => null,
        ];

        $sanitizedData = $this->contactService->sanitizeContactData($rawData);

        $this->assertEquals('', $sanitizedData['name']);
        $this->assertEquals('', $sanitizedData['email']);
        $this->assertEquals('', $sanitizedData['message']);
    }

    public function test_sanitize_contact_data_preserves_valid_content()
    {
        $rawData = [
            'name' => 'John O\'Connor-Smith Jr.',
            'email' => 'john.oconnor+test@example-domain.com',
            'message' => 'I\'m interested in your equipment. Can you provide more details about pricing and availability?',
        ];

        $sanitizedData = $this->contactService->sanitizeContactData($rawData);

        $this->assertEquals('John O\'Connor-Smith Jr.', $sanitizedData['name']);
        $this->assertEquals('john.oconnor+test@example-domain.com', $sanitizedData['email']);
        $this->assertEquals('I\'m interested in your equipment. Can you provide more details about pricing and availability?', $sanitizedData['message']);
    }

    public function test_admin_email_configuration()
    {
        // Test that admin email is retrieved from config
        config(['mail.admin_email' => 'admin@gymequipment.com']);
        config(['mail.from.address' => 'default@example.com']);

        $adminEmail = config('mail.admin_email', config('mail.from.address'));
        $this->assertEquals('admin@gymequipment.com', $adminEmail);
    }

    public function test_admin_email_fallback_configuration()
    {
        // Test fallback to mail.from.address when admin_email is not set
        config(['mail.admin_email' => null]);
        config(['mail.from.address' => 'default@example.com']);

        // The config helper returns null when the first key is null, then falls back to second
        $adminEmail = config('mail.admin_email') ?: config('mail.from.address');
        $this->assertEquals('default@example.com', $adminEmail);
    }
}