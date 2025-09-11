<?php

namespace Tests\Feature;

use App\Mail\ContactFormMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailFunctionalityTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_form_sends_email_to_admin()
    {
        Mail::fake();

        $contactData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'I am interested in your gym equipment. Please contact me with more information about your treadmills and pricing.',
        ];

        $response = $this->post(route('contact.store'), $contactData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        Mail::assertSent(ContactFormMail::class, function ($mail) use ($contactData) {
            return $mail->contactData['name'] === $contactData['name'] &&
                   $mail->contactData['email'] === $contactData['email'] &&
                   $mail->contactData['message'] === $contactData['message'];
        });
    }

    public function test_contact_form_email_contains_correct_data()
    {
        Mail::fake();

        $contactData = [
            'name' => 'Jane Smith',
            'email' => 'jane.smith@example.com',
            'message' => 'Hello, I would like to know more about your strength training equipment. Do you offer bulk discounts?',
        ];

        $this->post(route('contact.store'), $contactData);

        Mail::assertSent(ContactFormMail::class, function ($mail) use ($contactData) {
            $this->assertEquals($contactData['name'], $mail->contactData['name']);
            $this->assertEquals($contactData['email'], $mail->contactData['email']);
            $this->assertEquals($contactData['message'], $mail->contactData['message']);
            $this->assertArrayHasKey('submitted_at', $mail->contactData);
            $this->assertArrayHasKey('ip_address', $mail->contactData);
            
            return true;
        });
    }

    public function test_contact_form_email_sent_to_correct_recipient()
    {
        Mail::fake();
        
        // Set admin email in config
        config(['mail.admin_email' => 'admin@gymequipment.com']);

        $contactData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'message' => 'Test message for email recipient verification.',
        ];

        $this->post(route('contact.store'), $contactData);

        Mail::assertSent(ContactFormMail::class, function ($mail) {
            return $mail->hasTo('admin@gymequipment.com');
        });
    }

    public function test_contact_form_email_has_correct_subject()
    {
        Mail::fake();

        $contactData = [
            'name' => 'Subject Test User',
            'email' => 'subject@example.com',
            'message' => 'Testing email subject line.',
        ];

        $this->post(route('contact.store'), $contactData);

        Mail::assertSent(ContactFormMail::class, function ($mail) {
            return str_contains($mail->subject, 'Contact Form') || 
                   str_contains($mail->subject, 'New Message') ||
                   str_contains($mail->subject, 'Gym Equipment');
        });
    }

    public function test_contact_form_email_uses_correct_template()
    {
        $contactData = [
            'name' => 'Template Test User',
            'email' => 'template@example.com',
            'message' => 'Testing email template rendering.',
            'submitted_at' => now()->toDateTimeString(),
            'ip_address' => '192.168.1.1',
        ];

        $mail = new ContactFormMail($contactData);
        
        $this->assertEquals('emails.contact-form', $mail->view);
        $this->assertEquals($contactData, $mail->contactData);
    }

    public function test_contact_form_email_renders_correctly()
    {
        $contactData = [
            'name' => 'Render Test User',
            'email' => 'render@example.com',
            'message' => 'Testing email template rendering with special characters: <>&"\'',
            'submitted_at' => now()->toDateTimeString(),
            'ip_address' => '10.0.0.1',
        ];

        $mail = new ContactFormMail($contactData);
        $rendered = $mail->render();

        $this->assertStringContainsString('Render Test User', $rendered);
        $this->assertStringContainsString('render@example.com', $rendered);
        $this->assertStringContainsString('Testing email template rendering', $rendered);
        $this->assertStringContainsString('10.0.0.1', $rendered);
        
        // Ensure HTML is properly escaped
        $this->assertStringNotContainsString('<>&"\'', $rendered);
    }

    public function test_contact_form_handles_email_sending_failure_gracefully()
    {
        // Don't fake mail to test actual failure handling
        config(['mail.mailer' => 'smtp']);
        config(['mail.mailers.smtp.host' => 'invalid-smtp-server.com']);
        config(['mail.mailers.smtp.port' => 587]);

        $contactData = [
            'name' => 'Failure Test User',
            'email' => 'failure@example.com',
            'message' => 'Testing email failure handling.',
        ];

        $response = $this->post(route('contact.store'), $contactData);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        
        $errorMessage = session('error');
        $this->assertStringContainsString('technical issue', $errorMessage);
    }

    public function test_contact_form_email_queue_configuration()
    {
        Mail::fake();

        $contactData = [
            'name' => 'Queue Test User',
            'email' => 'queue@example.com',
            'message' => 'Testing email queue configuration.',
        ];

        $this->post(route('contact.store'), $contactData);

        // Verify email is queued if queue is configured
        if (config('queue.default') !== 'sync') {
            Mail::assertQueued(ContactFormMail::class);
        } else {
            Mail::assertSent(ContactFormMail::class);
        }
    }

    public function test_contact_form_email_with_special_characters()
    {
        Mail::fake();

        $contactData = [
            'name' => 'José María González-López',
            'email' => 'jose.maria@example.com',
            'message' => 'Hola! I\'m interested in your equipment. Can you provide información about precios?',
        ];

        $response = $this->post(route('contact.store'), $contactData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        Mail::assertSent(ContactFormMail::class, function ($mail) use ($contactData) {
            return $mail->contactData['name'] === $contactData['name'] &&
                   $mail->contactData['email'] === $contactData['email'] &&
                   $mail->contactData['message'] === $contactData['message'];
        });
    }

    public function test_contact_form_email_with_long_message()
    {
        Mail::fake();

        $longMessage = str_repeat('This is a very long message about gym equipment. ', 50);
        
        $contactData = [
            'name' => 'Long Message User',
            'email' => 'longmessage@example.com',
            'message' => $longMessage,
        ];

        $response = $this->post(route('contact.store'), $contactData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        Mail::assertSent(ContactFormMail::class, function ($mail) use ($contactData) {
            return $mail->contactData['message'] === $contactData['message'];
        });
    }

    public function test_multiple_contact_form_submissions_send_separate_emails()
    {
        Mail::fake();

        $contactData1 = [
            'name' => 'First User',
            'email' => 'first@example.com',
            'message' => 'First message about treadmills.',
        ];

        $contactData2 = [
            'name' => 'Second User',
            'email' => 'second@example.com',
            'message' => 'Second message about weight machines.',
        ];

        $this->post(route('contact.store'), $contactData1);
        $this->post(route('contact.store'), $contactData2);

        Mail::assertSent(ContactFormMail::class, 2);
        
        Mail::assertSent(ContactFormMail::class, function ($mail) use ($contactData1) {
            return $mail->contactData['name'] === $contactData1['name'];
        });
        
        Mail::assertSent(ContactFormMail::class, function ($mail) use ($contactData2) {
            return $mail->contactData['name'] === $contactData2['name'];
        });
    }

    public function test_contact_form_email_includes_metadata()
    {
        Mail::fake();

        $contactData = [
            'name' => 'Metadata Test User',
            'email' => 'metadata@example.com',
            'message' => 'Testing metadata inclusion in email.',
        ];

        $this->post(route('contact.store'), $contactData);

        Mail::assertSent(ContactFormMail::class, function ($mail) {
            $this->assertArrayHasKey('submitted_at', $mail->contactData);
            $this->assertArrayHasKey('ip_address', $mail->contactData);
            $this->assertNotNull($mail->contactData['submitted_at']);
            $this->assertNotNull($mail->contactData['ip_address']);
            
            return true;
        });
    }
}