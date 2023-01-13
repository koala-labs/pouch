<?php

namespace Koala\Pouch\Tests\Seeds;

use Illuminate\Support\Facades\Artisan;
use Koala\Pouch\Tests\DBTestCase;
use Koala\Pouch\Tests\Models\Post;
use Koala\Pouch\Tests\Models\Reaction;
use Koala\Pouch\Tests\Models\Tag;
use Koala\Pouch\Tests\Models\User;

class FilterDataSeederTest extends DBTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Artisan::call('db:seed', [
            '--class' => FilterDataSeeder::class
        ]);
    }

    public function testItCanSeedUsers()
    {
        $this->assertNotEmpty(User::all());
        $this->assertNotEmpty(Post::all());
        $this->assertNotEmpty(Tag::all());
        $this->assertNotEmpty(Reaction::all());

        $user = User::first();

        $this->assertNotEmpty($user->posts);
        $this->assertNotEmpty($user->reactions);
    }
}
