<?php

namespace Eril\Auth\Tests;

use Eril\Auth\Profile\Profile;
use PHPUnit\Framework\TestCase;

final class ProfileTest extends TestCase
{
    public function test_profile_supports_property_and_array_access(): void
    {
        $profile = new Profile([
            'bio' => 'Developer',
            'experience' => 5,
        ]);

        $this->assertSame('Developer', $profile->bio);
        $this->assertSame('Developer', $profile['bio']);
        $this->assertSame(5, $profile->get('experience'));
    }
}