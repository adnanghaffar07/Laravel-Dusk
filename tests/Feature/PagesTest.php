<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Class PagesTest
 * @package Tests\Feature
 */
class PagesTest extends TestCase
{
    use WithFaker, RefreshDatabase;

    /**
     * Setup the test environment.
     *
     * @throws \Throwable
     */
    public function setUp()
    {
        parent::setUp();
    }

    /**
     *  Clean up the testing environment before the next test.
     */
    public function tearDown()
    {
        parent::tearDown();
    }

    /**
     * Test home page
     * URL: /home
     * Behaviour: success, redirect to main page
     */
    public function test_home_page_redirect_success()
    {
        // Load test route
        $response = $this->get(url('/home'));
        $response->assertStatus(302);
        $response->assertSessionMissing('errors');
        $response->assertRedirect(url('/'));

        // Follow redirect
        $response2 = $this->get($response->getTargetUrl());
        $response2->assertStatus(200);
        $response2->assertSessionMissing('errors');
        $response2->assertSee('Securing Wire Transfers for Real Estate');
    }

    /**
     * Test main page
     * URL: /
     * Behaviour: success
     */
    public function test_main_page_success()
    {
        // Load test route
        $response = $this->get(route('home'));
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('Securing Wire Transfers for Real Estate');
    }

    /**
     * Test main page
     * URL: /about
     * Behaviour: success
     */
    public function test_about_page_success()
    {
        // Load test route
        $response = $this->get(route('about'));
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('About BuyerDocs');
    }

    /**
     * Test news page
     * URL: /news
     * Behaviour: success
     */
    public function test_news_page_success()
    {
        // Load test route
        $response = $this->get(route('news'));
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('In the News');
    }

    /**
     * Test contact page
     * URL: /contact
     * Behaviour: success
     */
    public function test_contact_page_success()
    {
        // Load test route
        $response = $this->get(route('contact'));
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('Contact Us');
    }

    /**
     * Test faq page
     * URL: /faq
     * Behaviour: success
     */
    public function test_faq_page_success()
    {
        // Load test route
        $response = $this->get(route('faq'));
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('What is BuyerDocs?');
    }

    /**
     * Test press release page
     * URL: /press
     * Behaviour: success
     */
    public function test_press_release_page_success()
    {
        // Load test route
        $response = $this->get(url('press'));
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('BuyerDocs to Rescue Residential and Commercial Real Estate from Wire Fraud');
    }

    /**
     * Test /blog/12042017 page
     * URL: /blog/12042017
     * Behaviour: success
     */
    public function test_blog_12042017_page_success()
    {
        // Load test route
        $response = $this->get(url('/blog/12042017'));
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('The Truths About Proprietary Systems for Title Companies');
    }

    /**
     * Test /blog/03222018 page
     * URL: /blog/03222018
     * Behaviour: success
     */
    public function test_blog_03222018_page_success()
    {
        // Load test route
        $response = $this->get(url('/blog/03222018'));
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('77 Facts About Cybercrime from ValueWalk');
    }

    /**
     * Test legal page
     * URL: /legal
     * Behaviour: success
     */
    public function test_legal_page_success()
    {
        // Load test route
        $response = $this->get(route('legal'));
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('Terms of Service - Client');
    }

    /**
     * Test legal page
     * URL: /privacy-policy
     * Behaviour: success
     */
    public function test_privacy_policy_page_success()
    {
        // Load test route
        $response = $this->get(route('privacy'));
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('Privacy Policy');
    }

    /**
     * Test terms-client page
     * URL: /terms-client
     * Behaviour: success
     */
    public function test_terms_client_page_success()
    {
        // Load test route
        $response = $this->get(route('terms_client'));
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('Terms of Service');
    }

    /**
     * Test terms-company page
     * URL: /terms-company
     * Behaviour: success
     */
    public function test_terms_company_page_success()
    {
        // Load test route
        $response = $this->get(route('terms_client'));
        $response->assertStatus(200);
        $response->assertSessionMissing('errors');
        $response->assertSee('Terms of Service');
    }
}
