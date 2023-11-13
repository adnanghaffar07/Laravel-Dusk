<?php

namespace Tests;

use App\Buyer;
use App\PostCloseClient;
use App\User;
use Laravel\Dusk\Browser;
use Laravel\Dusk\TestCase as BaseTestCase;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;

abstract class DuskTestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Prepare for Dusk test execution.
     *
     * @beforeClass
     * @return void
     */
    public static function prepare()
    {
        static::startChromeDriver();
    }

    public function setUp()
    {
        parent::setUp();

        // refresh and refill database with data only in case if it is empty (for perfomance)
        try {
            $user = User::first(); // if "users" table doesn't exist, this will throw an exception
            if (!$user) { // if "users" table exist, but it is empty
                throw new \Exception();
            }
        } catch (\Exception $e) {
            $this->artisan('migrate:fresh');
            $this->seed();
            $this->seed('FeatureTestsSeeder');

            $user = User::where('email', 'test@test.test')->first();
            $user->password = bcrypt('currentPassword1');
            $user->saveOrFail();
        }

        // For some strange reason, sleep() prevents errors between tests
        // See https://github.com/laravel/dusk/issues/105
        sleep(1);

        $this->browse(function (Browser $browser) {
            $browser->resize(1900, 1000);
        });
    }

    protected function clearCache()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit(route('cache.clear')); // clear login attempts and other cache stuff
        });
    }

    /**
     * @param Buyer $buyer
     * @param Browser $browser
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    protected function loginBuyer(Buyer $buyer, Browser $browser)
    {
        $browser->visit(route('buyerLogin', ['subdomain' => env('GENERIC_SUBDOMAIN')]))
            ->waitForText('BUYERS / LENDERS');

        $browser->type('email', $buyer->email)
            ->select('title_id', '1')
            ->click('.custom-checkbox')
            ->press('Login')
            ->waitForText('BUYERS / LENDERS');

        // Click in modal to accept warning
        $browser->waitFor('#warnModal.modal.fade.show')
            ->pause(500)                // allow modal to open
            ->waitForText('Accept')
            ->press('Accept')
            ->waitForText('DO NOT TRUST ANY OTHER WIRE INSTRUCTIONS')
            ->assertSee('DO NOT TRUST ANY OTHER WIRE INSTRUCTIONS');
    }

    /**
     * @param Browser $browser
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    protected function logoutBuyer(Browser $browser)
    {
        $browser->visit(route('buyerLogin', ['subdomain' => env('GENERIC_SUBDOMAIN')]))
            ->waitForText('DO NOT TRUST ANY OTHER WIRE INSTRUCTIONS')
            ->assertSee('DO NOT TRUST ANY OTHER WIRE INSTRUCTIONS');

        // Logout
        $browser->clickLink('Logout')
            ->waitForText('LOGIN')
            ->assertSee('LOGIN');
    }

    /**
     * @param PostCloseClient $client
     * @param Browser $browser
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    protected function loginPostClose(PostCloseClient $client, Browser $browser)
    {
        $browser->visit(route('postCloseClientLogin', ['subdomain' => env('GENERIC_SUBDOMAIN')]))
            ->waitForText('CLIENT LOGIN')
            ->assertSee('CLIENT LOGIN')
            ->assertSee($client->title->name);

        $browser->type('email', $client->email)
            ->type('accessCode', $client->token)
            ->press('Login')
            ->waitForText('Thank you for trusting ' . $client->title->name . ' with your closing!')
            ->assertSee('Thank you for trusting ' . $client->title->name . ' with your closing!');
    }

    /**
     * @param Browser $browser
     * @throws \Facebook\WebDriver\Exception\TimeOutException
     */
    protected function logoutPostClose(Browser $browser)
    {
        $browser->visit(route('postCloseClientLogin', ['subdomain' => env('GENERIC_SUBDOMAIN')]))
            ->waitForText('Thank you for trusting')
            ->assertSee('Thank you for trusting');

        // Logout
        $browser->clickLink('Logout')
            ->waitForText('LOGIN')
            ->assertSee('LOGIN');
    }

    /**
     * Create the RemoteWebDriver instance.
     *
     * @return \Facebook\WebDriver\Remote\RemoteWebDriver
     */
    protected function driver()
    {
        $options = (new ChromeOptions)->addArguments([
            '--disable-gpu',
            '--headless',
        ]);

        return RemoteWebDriver::create(
            'http://localhost:9515', DesiredCapabilities::chrome()->setCapability(
            ChromeOptions::CAPABILITY, $options
        )
        );
    }
}
