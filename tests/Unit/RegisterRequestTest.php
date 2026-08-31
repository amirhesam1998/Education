<?php

namespace Tests\Unit;

use App\Http\Requests\RegisterRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RegisterRequestTest extends TestCase
{
    #[Test]
    public function it_defines_signup_validation_rules(): void
    {
        $rules = (new RegisterRequest())->rules();

        $this->assertContains('required', $rules['name']);
        $this->assertContains('required', $rules['phone']);
        $this->assertContains('unique:users,phone', $rules['phone']);
        $this->assertContains('nullable', $rules['email']);
        $this->assertContains('unique:users,email', $rules['email']);
        $this->assertContains('confirmed', $rules['password']);
        $this->assertContains('max:30', $rules['phone']);
    }
}
