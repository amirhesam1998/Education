<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_public_reservation_request_form_is_available(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('درخواست تایم انتخاب رشته');
    }
}
