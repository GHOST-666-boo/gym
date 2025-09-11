<?php

namespace Tests\Feature;

use App\Mail\ContactFormMail;
use App\Services\ContactService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_form_displays_correctly()
    {
        $response = $this->get(route('contact'));

        $response->assertStatus(200);
        $response->assertViewIs('public.contact');
        $response->assertSee('Contact Us');
        $response->assertSee('Send Us a Message');
    }

    public function test_contact_form_submission_with_valid_data()
    {
        Mail::fake();

        $contactData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'I am interested in your gym equipment. Please contact me with more information about your treadmills.'
        ];

        $response = $this->post(route('contact.store'), $contactData);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertStringContainsString('Thank you for your message, John Doe!', session('success'));

        // Assert that the email was sent
        Mail::assertSent(ContactFormMail::class, function ($mail) use ($contactData) {
            return $mail->contactData['name'] === $contactData['name'] &&
                   $mail->contactData['email'] === $contactData['email'] &&
                   $mail->contactData['message'] === $contactData['message'];
        });
    }

    public function test_contact_form_validation_errors()
    {
        $response = $this->post(route('contact.store'), []);

        $response->assertSessionHasErrors(['name', 'email', 'message']);
    }

    public function test_contact_form_validation_with_invalid_email()
    {
        $response = $this->post(route('contact.store'), [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'message' => 'This is a test message.'
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_contact_form_validation_with_short_message()
    {
        $response = $this->post(route('contact.store'), [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'Short'
        ]);

        $response->assertSessionHasErrors(['message']);
    }

    public function test_contact_service_sanitizes_data_correctly()
    {
        $contactService = new ContactService();

        $rawData = [
            'name' => '  <script>alert("xss")</script>John Doe  ',
            'email' => '  JOHN@EXAMPLE.COM  ',
            'message' => '  <b>This is a message</b> with HTML tags.  '
        ];

        $sanitizedData = $contactService->sanitizeContactData($rawData);

        $this->assertEquals('John Doe', $sanitizedData['name']);
        $this->assertEquals('john@example.com', $sanitizedData['email']);
        $this->assertEquals('This is a message with HTML tags.', $sanitizedData['message']);
        $this->assertArrayHasKey('submitted_at', $sanitizedData);
        $this->assertArrayHasKey('ip_address', $sanitizedData);
    }

    public function test_contact_form_handles_mail_sending_failure()
    {
        // Mock the ContactService to simulate email failure
        $this->mock(ContactService::class, function ($mock) {
            $mock->shouldReceive('sanitizeContactData')
                 ->once()
                 ->andReturn([
                     'name' => 'John Doe',
                     'email' => 'john@example.com',
                     'message' => 'Test message',
                     'submitted_at' => now()->toDateTimeString(),
                     'ip_address' => '127.0.0.1'
                 ]);
            
            $mock->shouldReceive('handleContactSubmission')
                 ->once()
                 ->andReturn(false);
        });

        $contactData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'I am interested in your gym equipment.'
        ];

        $response = $this->post(route('contact.store'), $contactData);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertStringContainsString('technical issue', session('error'));
    }
}