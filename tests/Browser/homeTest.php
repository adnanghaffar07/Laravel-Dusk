<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class homeTest extends DuskTestCase
{
    /**
     * @group home
     *
     * @return void
     */
    public function testView()
    {
        // From homepage, visit home buyer page and login
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                    ->waitfortext('Securing Wire Transfers for Real Estate')
                    ->assertSee('Get Started')
                    ->assertSee('Learn More');
        });
    }
}
