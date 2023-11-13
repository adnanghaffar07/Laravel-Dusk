<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use App\Mail\Contact;

/**
 * Class ContactControllerTest
 * @package Tests\Feature\Controllers
 */
class ContactControllerTest extends TestCase
{
    use WithFaker, RefreshDatabase;

    /**
     * @var
     */
    public $email;

    /**
     * @var
     */
    public $name;

    /**
     * @var
     */
    public $company;

    /**
     * @var
     */
    public $position;

    /**
     * @var
     */
    public $message;

    /**
     * Setup the test environment.
     *
     * @throws \Throwable
     */
    public function setUp()
    {
        parent::setUp();

        $this->email = $this->faker->email;
        $this->name = $this->faker->name;
        $this->company = $this->faker->company;
        $this->position = $this->faker->jobTitle;
        $this->message = $this->faker->realText($maxNbChars = 200, $indexSize = 2);
    }

    /**
     *  Clean up the testing environment before the next test.
     */
    public function tearDown()
    {
        parent::tearDown();
    }

    /**
     * Test contact method
     * Behaviour: success
     */
    public function test_contact_method_success()
    {
        // Load test route
        $response = $this->get(url('/contact'));
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('Contact Us');
    }

    /**
     * Test processForm method
     * Behaviour: success
     */
    public function test_process_form_method_success()
    {
        Mail::fake();

        // Load test route
        $response = $this->post(url('/contact'), [
            'name' => $this->name,
            'email' => $this->email,
            'company' => $this->company,
            'position' => $this->position,
            'message' => $this->message,
            'g-recaptcha-response' => 'ok',
        ]);

        $response->assertStatus(302);
        $response->assertSessionMissing('errors');
        $response->assertSessionHas('status_success', 'Thank you for contacting us! We will be in touch shortly. A copy of this request has been emailed to you.');
        Mail::assertQueued(Contact::class, function ($mail) {
            return $mail->hasTo($this->email) && $mail->hasBcc('contact@buyerdocs.com');
        });
    }

    /**
     * Test processForm method, without required fields
     * Behaviour: fail
     */
    public function test_process_form_method_fail_validation_fields_is_required()
    {
        // Load test route
        $response = $this->post(url('/contact'), []);
        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'name' => 'The name field is required.',
            'email' => 'The email field is required.',
            'company' => 'The company field is required.',
            'position' => 'The position field is required.',
            'message' => 'The message field is required.',
            'g-recaptcha-response' => 'The g-recaptcha-response field is required.',
        ]);
    }
}
