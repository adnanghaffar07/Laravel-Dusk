<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class aboutTest extends DuskTestCase
{
    /**
     * @group about
     *
     * @return void
     */
    public function testView()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit(route('about'))
                    ->waitForText('About BuyerDocs')
                    ->assertSee('SEE IT IN ACTION');
        });
    }

    // /**
    // * @group about
    // *
    // */
    // public function testAdminDemoLink()
    // {
    //     // $this->browse(function (Browser $browser) {
    //     //     $browser->visit(route('about'))
    //     //             // ->clickLink('ABOUT')
    //     //             ->assertSee('user@demo.com');
    //     // });
    // }

    // /**
    // * @group about
    // *
    // */
    // public function testAdminDemoLink()
    // {
    //     $this->browse(function (Browser $browser) {
    //         $browser->visit(route('about'))
    //                 ->clickLink('begin_admin_demo')
    //                 ->assertSee('user@demo.com');
    //     });
    // }
}
