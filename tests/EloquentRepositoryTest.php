<?php

namespace Koala\Pouch\Tests;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Koala\Pouch\Contracts\AccessControl;
use Koala\Pouch\Tests\Models\Tag;
use Koala\Pouch\Tests\Seeds\FilterDataSeeder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Koala\Pouch\Tests\Models\User;
use Koala\Pouch\Tests\Models\Post;
use Koala\Pouch\EloquentRepository;
use Koala\Pouch\Tests\Models\Profile;
use Illuminate\Database\Eloquent\Builder;

class EloquentRepositoryTest extends DBTestCase
{
    /**
     * Retrieve a sample repository for testing.
     *
     * @param string|null $model_class
     * @param array $input
     *
     * @return \Koala\Pouch\Contracts\Repository|\Koala\Pouch\EloquentRepository
     */
    private function getRepository($model_class = null, array $input = [])
    {
        if (!is_null($model_class)) {
            $repository = (new EloquentRepository())->setModelClass($model_class)->setInput($input);
            $repository->accessControl()->setDepthRestriction(3);

            return $repository;
        }

        return new EloquentRepository();
    }

    public function seedUsers()
    {
        Artisan::call('db:seed', [
            '--class' => FilterDataSeeder::class
        ]);
    }

    public function testItRejectsNonEloquentModels()
    {
        $this->expectExceptionMessage(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Specified model class must be an instance of Illuminate\Database\Eloquent\Model');
        (new EloquentRepository())->setModelClass('AwkwardKoala');
    }

    public function testItCanCreateASimpleModel()
    {
        $user = $this->getRepository(User::class)->save();
        $this->assertNotNull($user);
        $this->assertEquals(1, $user->id);
    }

    public function testItCanFindASimpleModel()
    {
        $repo       = $this->getRepository(User::class);
        $user       = $repo->save();
        $found_user = $repo->find($user->id);
        $this->assertNotNull($found_user);
        $this->assertEquals($user->id, $found_user->id);
    }

    public function testItCanReturnASingleModelFromAQuery()
    {
        $this->seedUsers();
        $repo            = $this->getRepository(User::class);
        $users           = $repo->all();
        $firstUser       = $repo->first();
        $firstOrFailUser = $repo->firstOrFail();
        $this->assertNotNull($firstUser);
        $this->assertNotNull($firstOrFailUser);
        $this->assertTrue($users->first()->is($firstUser));
        $this->assertTrue($users->first()->is($firstOrFailUser));
        $this->assertTrue($firstUser->is($firstOrFailUser));
    }

    public function testItFirstReturnsNullWhenTheQueryHasNoResults()
    {
        $model = new class() extends User {
            public static function query()
            {
                return \Mockery::mock(parent::query())
                    ->shouldReceive('first')
                    ->andReturn(null)
                    ->getMock();
            }
        };

        $this->assertNull($this->getRepository(get_class($model))->first());
    }

    public function testItFailsWhenFirstOrFailQueryHasNoResults()
    {
        $model = new class() extends User {
            public static function query()
            {
                return \Mockery::mock(parent::query())
                    ->shouldReceive('first')
                    ->andReturn(null)
                    ->getMock();
            }
        };

        $this->expectException(ModelNotFoundException::class);

        $this->getRepository(get_class($model))->firstOrFail();
    }

    public function testItCountsCollections()
    {
        $repository = $this->getRepository(User::class);
        $this->assertEquals($repository->count(), 0);
        $this->assertFalse($repository->hasAny());
    }

    public function testItPaginates()
    {
        $repository  = $this->getRepository(User::class);
        $first_user  = $repository->setInput(['username' => 'bob'])->save();
        $second_user = $repository->setInput(['username' => 'sue'])->save();

        $paginator = $repository->paginate(1);
        $this->assertInstanceOf('Illuminate\Pagination\LengthAwarePaginator', $paginator);
        $this->assertTrue($paginator->hasMorePages());
    }

    public function testItEagerLoadsRelationsSafely()
    {
        $this->getRepository(User::class, [
            'username' => 'joe',
            'posts'    => [
                [
                    'title' => 'Some Great Post',
                ],
            ]
        ])->save();

        $repository = $this->getRepository(User::class);
        $repository->modify()
            ->setFilters(['username' => 'joe'])
            ->setEagerLoads([
                'posts.nothing',
                'nada'
            ]);
        $user = $repository->all()->first();


        $this->assertNotNull($user);
        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $user->posts);
        $this->assertInstanceOf(Post::class, $user->posts->first());
    }

    public function testItCanFillModelFields()
    {
        $user = $this->getRepository(User::class, ['username' => 'bob'])->save();
        $this->assertNotNull($user);
        $this->assertEquals($user->username, 'bob');
    }

    public function testItUpdatesExistingModels()
    {
        $user = $this->getRepository(User::class, ['username' => 'bobby'])->save();
        $this->assertEquals($user->id, 1);
        $this->assertEquals($user->username, 'bobby');

        $user = $this->getRepository(User::class, [
            'id'       => 1,
            'username' => 'sue'
        ])->save();
        $this->assertEquals($user->id, 1);
        $this->assertEquals($user->username, 'sue');
    }

    public function testItDeletesModels()
    {
        $user = $this->getRepository(User::class, ['username' => 'spammer'])->save();
        $this->assertEquals($user->id, 1);
        $this->assertTrue($user->exists());

        $this->getRepository(User::class, ['id' => 1])->delete();
        $this->assertNull(User::find(1));
    }

    public function testItFillsBelongsToRelations()
    {
        $post = $this->getRepository(Post::class, [
            'title' => 'Some Great Post',
            'user'  => [
                'username' => 'jimmy',
            ],
        ])->save();

        $this->assertNotNull($post->user);
        $this->assertEquals($post->user->username, 'jimmy');
    }

    public function testItFillsHasManyRelations()
    {
        $user = $this->getRepository(User::class, [
            'username' => 'joe',
            'posts'    => [
                [
                    'title' => 'Some Great Post',
                ],
                [
                    'title' => 'Yet Another Great Post',
                ],
            ]
        ])->save();

        $this->assertEquals($user->posts->pluck('id')->toArray(), [
            1,
            2
        ]);

        $post = Post::find(2);
        $this->assertNotNull($post);
        $this->assertEquals($post->user_id, $user->id);
        $this->assertEquals($post->title, 'Yet Another Great Post');

        $this->getRepository(User::class, [
            'id'    => $user->id,
            'posts' => [
                [
                    'id' => 1,
                ],
            ],
        ])->save();

        $user->load('posts');

        $this->assertEquals($user->posts->pluck('id')->toArray(), [
            1,
        ]);

        $post = Post::find(2);
        $this->assertNull($post);
    }

    public function testItFillsHasOneRelations()
    {
        $user = $this->getRepository(User::class, [
            'username' => 'joe',
            'profile'  => [
                'favorite_cheese' => 'brie',
            ],
        ])->save();

        $this->assertNotNull($user->profile);
        $this->assertEquals($user->profile->favorite_cheese, 'brie');
        $old_profile_id = $user->profile->id;

        $user = $this->getRepository(User::class, [
            'id'      => $user->id,
            'profile' => [
                'favorite_cheese' => 'pepper jack',
            ],
        ])->save();

        $this->assertNotNull($user->profile);
        $this->assertEquals($user->profile->favorite_cheese, 'pepper jack');

        $this->assertNotEquals($user->profile->id, $old_profile_id);
        $this->assertNull(Profile::find($old_profile_id));
    }

    public function testItCascadesThroughSupportedRelations()
    {
        $post = $this->getRepository(Post::class, [
            'title' => 'All the Tags',
            'user'  => [
                'username' => 'simon',
                'profile'  => [
                    'favorite_cheese' => 'brie',
                ],
            ],
            'tags' => [
                [
                    'label' => 'Important Stuff',
                ],
                [
                    'label' => 'Less Important Stuff',
                ],
            ],
        ])->save();

        $this->assertEquals($post->tags()->count(), 2);
        $this->assertNotNull($post->user->profile);
        $this->assertNotNull($post->user->profile->favorite_cheese, 'brie');
    }

    public function testItUpdatesBelongsToManyPivots()
    {
        $post = $this->getRepository(Post::class, [
            'title' => 'All the Tags',
            'user'  => [
                'username' => 'josh',
            ],
            'tags'  => [
                [
                    'label' => 'Has Extra',
                    'pivot' => [
                        'extra' => 'Meowth'
                    ],
                ],
            ],
        ])->save();

        $tag = $post->tags->first();
        $this->assertEquals($tag->pivot->extra, 'Meowth');

        $post = $this->getRepository(Post::class, [
            'id'   => $post->id,
            'tags' => [
                [
                    'id'    => $tag->id,
                    'pivot' => [
                        'extra' => 'Pikachu',
                    ],
                ],
            ],
        ])->save();

        $tag = $post->tags->first();
        $this->assertEquals($tag->pivot->extra, 'Pikachu');
    }

    public function testItSorts()
    {
        $repository = $this->getRepository(User::class);
        $first_user = $repository->setInput([
            'username' => 'Bobby'
        ])->save();
        $second_user = $repository->setInput([
            'username' => 'Robby'
        ])->save();
        $this->assertEquals($repository->all()->count(), 2);

        $repository->modify()->setSortOrder([
            'id' => 'desc'
        ]);

        $found_users = $repository->all();

        $this->assertEquals($found_users->count(), 2);
        $this->assertEquals($found_users->first()->id, 2);
    }

    public function testItSortsNested()
    {
        $repository = $this->getRepository(User::class);
        $first_user = $repository->setInput([
            'username' => 'Bobby',
            'posts'    => [
                [
                    'title' => 'First Post',
                    'tags'  => [
                        ['label' => 'Tag1']
                    ]
                ]
            ]
        ])->save();
        $second_user = $repository->setInput([
            'username' => 'Robby',
            'posts'    => [
                [
                    'title' => 'Zis is the final post alphabetically',
                    'tags'  => [
                        ['label' => 'Tag2']
                    ]
                ]
            ]
        ])->save();
        $third_user = $repository->setInput([
            'username' => 'Gobby',
            'posts'    => [
                [
                    'title' => 'Third Post',
                    'tags'  => [
                        ['label' => 'Tag3']
                    ]
                ]
            ]
        ])->save();
        $this->assertEquals($repository->all()->count(), 3);

        $repository->modify()->setSortOrder([
            'posts.title' => 'desc'
        ]);

        $found_users = $repository->all();

        $this->assertEquals($found_users->count(), 3);
        $this->assertEquals($found_users->first()->username, 'Robby');
    }

    public function testItModifiesQueries()
    {
        $repository = $this->getRepository(User::class, ['username' => 'Billy']);
        $repository->save();
        $this->assertEquals($repository->count(), 1);
        $repository->modify()->set([
            function (Builder $query) {
                $query->whereRaw(DB::raw('0 = 1'));
            }
        ]);
        $this->assertEquals($repository->count(), 0);
    }

    public function testItAddsModifier()
    {
        $repository = $this->getRepository(User::class, ['username' => 'Billy']);
        $repository->save();
        $this->assertEquals($repository->count(), 1);
        $repository->modify()->add(function (Builder $query) {
            $query->whereRaw(DB::raw('0 = 1'));
        });

        $this->assertSame(1, count($repository->modify()->getModifiers()));
        $this->assertEquals($repository->count(), 0);
    }

    public function testItCanFilterOnFields()
    {
        $this->seedUsers();

        // Test that the repository implements filters correctly
        $repository = $this->getRepository(User::class);
        $this->assertEquals(User::count(), $repository->all()->count());

        $repository->modify()->setFilters(['username' => '=chewbaclava@galaxyfarfaraway.com']);
        $found_users = $repository->all();

        $this->assertEquals($found_users->count(), 1);
        $this->assertEquals($found_users->first()->username, 'chewbaclava@galaxyfarfaraway.com');
    }

    public function testItOnlyUpdatesFillableAttributesOnCreate()
    {
        $input = [
            'username'       => 'javacup@galaxyfarfaraway.com',
            'name'           => 'Jabba The Hutt',
            'hands'          => 10,
            'times_captured' => 0,
            'not_fillable'   => 'should be null',
            'occupation'     => 'Being Gross',
            'profile'        => [
                'favorite_cheese' => 'Cheddar',
                'favorite_fruit'  => 'Apples',
                'is_human'        => false
            ],
        ];

        $user = $this->getRepository(User::class, $input)->save();
        $this->assertNull($user->not_fillable);
    }

    public function testItOnlyUpdatesFillableAttributesOnUpdate()
    {
        $input = [
            'username'       => 'javacup@galaxyfarfaraway.com',
            'name'           => 'Jabba The Hutt',
            'hands'          => 10,
            'times_captured' => 0,
            'not_fillable'   => 'should be null',
            'occupation'     => 'Being Gross',
            'profile'        => [
                'favorite_cheese' => 'Cheddar',
                'favorite_fruit'  => 'Apples',
                'is_human'        => false
            ],
        ];

        $user = $this->getRepository(User::class, $input)->save();
        $this->assertNull($user->not_fillable);

        $input['id'] = $user->id;
        $user = $this->getRepository(User::class, $input)->update();
        $this->assertNull($user->not_fillable);
    }

    public function testItOnlyUpdatesFillableAttributesForRelationsOnCreate()
    {
        $input = [
            'username'       => 'javacup@galaxyfarfaraway.com',
            'name'           => 'Jabba The Hutt',
            'hands'          => 10,
            'times_captured' => 0,
            'not_fillable'   => 'should be null',
            'occupation'     => 'Being Gross',
            'profile'        => [
                'favorite_cheese' => 'Cheddar',
                'favorite_fruit'  => 'Apples',
                'is_human'        => false,
                'not_fillable'    => 'should be null'
            ],
        ];

        $user = $this->getRepository(User::class, $input)->save();
        $this->assertNull($user->not_fillable);
        $this->assertNull($user->profile->not_fillable);
    }

    public function testItOnlyUpdatesFillableAttributesForRelationsOnUpdate()
    {
        $input = [
            'username'       => 'javacup@galaxyfarfaraway.com',
            'name'           => 'Jabba The Hutt',
            'hands'          => 10,
            'times_captured' => 0,
            'not_fillable'   => 'should be null',
            'occupation'     => 'Being Gross',
            'profile'        => [
                'favorite_cheese' => 'Cheddar',
                'favorite_fruit'  => 'Apples',
                'is_human'        => false,
                'not_fillable'    => 'should be null'
            ],
        ];

        $user = $this->getRepository(User::class, $input)->save();
        $this->assertNull($user->not_fillable);
        $this->assertNull($user->profile->not_fillable);

        $input['id'] = $user->id;
        $user = $this->getRepository(User::class, $input)->update();
        $this->assertNull($user->not_fillable);
        $this->assertNull($user->profile->not_fillable);
    }

    public function testItDoesNotRunArbitraryMethodsOnActualInstance()
    {
        $input = [
            'username'       => 'javacup@galaxyfarfaraway.com',
            'name'           => 'Jabba The Hutt',
            'hands'          => 10,
            'times_captured' => 0,
            'not_fillable'   => 'should be null',
            'occupation'     => 'Being Gross',
        ];

        $user = $this->getRepository(User::class, $input)->save();
        $this->assertNotNull($user);

        $input['delete'] = 'doesn\'t matter but this should not be run';
        $input['id'] = $user->id;

        // Since users are soft deletable, if this fails and we run a $user->delete(), magic box will delete the record
        // but then try to recreate it with the same ID and get a MySQL unique constraint error because the
        // original ID record exists but is soft deleted
        $user = $this->getRepository(User::class, $input)->update();

        $database_user = User::find($user->id);

        $this->assertNotNull($database_user);
        $this->assertNull($user->deleted_at);
    }

    public function testItCanSetDepthRestriction()
    {
        $input = [
            'username'       => 'javacup@galaxyfarfaraway.com',
            'name'           => 'Jabba The Hutt',
            'hands'          => 10,
            'times_captured' => 0,
            'not_fillable'   => 'should be null',
            'occupation'     => 'Being Gross',
        ];

        $repository = $this->getRepository(User::class, $input);
        $this->assertEquals(3, $repository->accessControl()->getDepthRestriction()); // getRepository sets 3 by default
        $repository->accessControl()->setDepthRestriction(5);
        $this->assertEquals(5, $repository->accessControl()->getDepthRestriction());
    }

    public function testItDepthRestrictsEagerLoads()
    {
        $this->seedUsers();

        $repository = $this->getRepository(User::class);
        $repository
            ->accessControl()
            ->setDepthRestriction(0);
        $repository->modify()->setEagerLoads(
            [
                'posts.tags',
            ]
        );
        $users = $repository->all()->toArray(); // toArray so we don't pull relations

        foreach ($users as $user) {
            $this->assertTrue(!isset($user['posts']));
            $this->assertTrue(!isset($user['posts']['tags'])); // We should load neither
        }

        $repository = $this->getRepository(User::class);
        $repository->accessControl()
            ->setDepthRestriction(1);
        $repository->modify()
            ->setEagerLoads([
                'posts',
                'posts.tags',
            ]);

        $users = $repository->all()->toArray(); // toArray so we don't pull relations

        foreach ($users as $user) {
            $this->assertTrue(isset($user['posts']));
            $this->assertTrue(isset($user['posts'][0]));
            $this->assertTrue(!isset($user['posts'][0]['tags'])); // We should load posts (1 level) but not tags (2 levels)
        }

        $repository = $this->getRepository(User::class);
        $repository->accessControl()
            ->setDepthRestriction(2);
        $repository->modify()
            ->setEagerLoads(
                [
                    'posts',
                    'posts.user',
                ]
            );
        $users = $repository->all()->toArray(); // toArray so we don't pull relations

        foreach ($users as $user) {
            $this->assertTrue(isset($user['posts']));
            $this->assertTrue(isset($user['posts'][0]));
            $this->assertTrue(isset($user['posts'][0]['user'])); // We should load both
        }
    }

    public function testItDepthRestrictsFilters()
    {
        $this->seedUsers();

        /**
         * Test with 0 depth, filter too long
         */
        $repository = $this->getRepository(User::class);
        $repository->accessControl()
            ->setDepthRestriction(0);
        $repository->modify()->setFilters([
            'posts.tags.label' => '=#mysonistheworst'
        ]);

        $users = $repository->all();

        // Filter should not apply because depth restriction is 0
        $this->assertEquals(User::all()->count(), $users->count());

        /**
         * Test with 1 depth, filter is allowed
         */
        $repository = $this->getRepository(User::class);
        $repository->accessControl()
            ->setDepthRestriction(1);
        $repository->modify()->setFilters([
            'posts.title' => '~10 Easy Ways to Clean'
        ]);
        $users = $repository->all();

        // Filter should apply because depth restriction is 1
        $this->assertEquals(1, $users->count());
        $this->assertEquals('solocup@galaxyfarfaraway.com', $users->first()->username);

        /**
         * Test with 1 depth, filter is too long
         */
        $repository = $this->getRepository(User::class);
        $repository->accessControl()
            ->setDepthRestriction(1);
        $repository->modify()->setFilters([
            'posts.tags.label' => '=#mysonistheworst'
        ]);
        $users = $repository->all();

        // Filter should apply because depth restriction is 1
        $this->assertEquals(User::all()->count(), $users->count());

        /**
         * Test with 1 depth, filter is okay
         */
        $repository = $this->getRepository(User::class);
        $repository->accessControl()
            ->setDepthRestriction(2);
        $repository->modify()->setFilters([
            'posts.tags.label' => '=#mysonistheworst'
        ]);
        $users = $repository->all();

        // Filter should not apply because depth restriction is 2
        $this->assertEquals(2, $users->count());

        foreach ($users as $user) {
            $this->assertTrue(in_array($user->username, ['solocup@galaxyfarfaraway.com', 'lorgana@galaxyfarfaraway.com']));
        }
    }

    /**
     * @dataProvider sortDirectionProvider
     */
    public function testItCanSortQuery(string $direction)
    {
        $this->seedUsers();
        $repository = $this->getRepository(User::class);
        $repository->modify()->setSortOrder(['times_captured' => $direction]);
        $users = $repository->all();

        $this->assertEquals(User::all()->count(), $users->count());
        $this->assertCollectionIsSorted($users->pluck('times_captured'), $direction);
    }

    /**
     * @dataProvider sortDirectionProvider
     */
    public function testItDepthRestrictsSorts(string $direction)
    {
        $this->seedUsers();

        /**
         * Sort depth zero, expect users in whichever order returned by DB
         */
        $repository = $this->getRepository(User::class);
        $repository->accessControl()
            ->setDepthRestriction(0);
        $repository->modify()
            ->setSortOrder(['profile.favorite_cheese' => $direction]);
        $users = $repository->all();

        $this->assertEquals(User::all()->count(), $users->count());
        $this->assertCollectionIsSorted($users);

        /**
         * Sort depth 1, expect sorting by favorite cheese, sorted alphabetical
         */
        $repository = $this->getRepository(User::class);
        $repository->accessControl()
            ->setDepthRestriction(1);
        $repository->modify()
            ->setSortOrder(['profile.favorite_cheese' => $direction]);
        $users = $repository->all();

        $this->assertEquals(User::all()->count(), $users->count());
        $this->assertCollectionIsSorted($users->pluck('profile.favorite_cheese'), $direction);
    }

    /**
     * @dataProvider sortDirectionProvider
     */
    public function testItCanSortBelongsToRelation(string $direction)
    {
        $this->seedUsers();
        /**
         * Sort depth 1, expect sorting by favorite cheese, asc alphabetical
         */
        $repository = $this->getRepository(Profile::class);
        $repository->modify()
            ->setSortOrder(['users.username' => $direction])
            ->setEagerLoads(['user']);
        $profiles = $repository->all();

        $this->assertCount(Profile::all()->count(), $profiles);
        $this->assertCollectionIsSorted($profiles->pluck('user.username'), $direction);
    }

    /**
     * @dataProvider sortDirectionProvider
     */
    public function testItCanSortBelongsToManyRelation(string $direction)
    {
        $this->seedUsers();

        /**
         * Sort depth 1, expect sorting by post title, asc alphabetical
         */
        $repository = $this->getRepository(Tag::class);
        $repository->modify()
            ->setSortOrder(['posts.title' => $direction])
            ->setEagerLoads(['posts']);
        $tags = $repository->all();

        $this->assertCount(Tag::count(), $tags->pluck('label')->unique());

        $tags->each(
            fn ($tag) => $this->assertCollectionIsSorted($tag->posts->pluck('title'), $direction)
        );
    }

    /**
     * @dataProvider sortDirectionProvider
     */
    public function testItCanSortBelongsToManyRelationDesc(string $direction)
    {
        $this->seedUsers();

        /**
         * Sort depth 1, expect sorting by post title, desc alphabetical
         */
        $repository = $this->getRepository(Tag::class);
        $repository->modify()
            ->setSortOrder(['posts.title' => $direction])
            ->setEagerLoads(['posts']);
        $tags = $repository->all();

        $this->assertCount(Tag::count(), $tags->pluck('label')->unique());

        $tags->each(
            fn ($tag) => $this->assertCollectionIsSorted($tag->posts->pluck('title'), $direction)
        );
    }

    public function testItCanAddMultipleAdditionalFilters()
    {
        $this->seedUsers();

        $otherUser = User::firstOrCreate(
            ['username' => 'bobbytables@xkcd.com'],
            [
                'username'       => 'bobbytables@xkcd.com',
                'name'           => 'Bobby',
                'hands'          => 2,
                'times_captured' => 0,
                'occupation'     => 'Student'
            ]
        );

        $repository = $this->getRepository(User::class);

        $this->assertEquals($allCount = User::count(), $repository->all()->count());

        $repository->modify()->setFilters(['username' => '~galaxyfarfaraway.com']);
        $found_users = $repository->all();

        $this->assertEquals($allCount - 1, $found_users->count());
        $this->assertFalse($found_users->contains($otherUser));

        $additional_filters = [
            'profile.is_human' => '=true',
            'times_captured'   => '>2'
        ];

        $repository->modify()->addFilters($additional_filters);
        $found_users = $repository->all();
        $this->assertEquals($found_users->count(), 2);

        $filters = $repository->modify()->getFilters();
        $this->assertEquals([
            'username'         => '~galaxyfarfaraway.com',
            'profile.is_human' => '=true',
            'times_captured'   => '>2'
        ], $filters);
    }

    public function testItCanAddOneAdditionalFilter()
    {
        $this->seedUsers();

        $otherUser = User::firstOrCreate(
            ['username' => 'bobbytables@xkcd.com'],
            [
                'username'       => 'bobbytables@xkcd.com',
                'name'           => 'Bobby',
                'hands'          => 2,
                'times_captured' => 0,
                'occupation'     => 'Student'
            ]
        );

        $repository = $this->getRepository(User::class);

        $this->assertEquals($allCount = User::count(), $repository->all()->count());

        $repository->modify()->setFilters(['username' => '~galaxyfarfaraway.com']);
        $found_users = $repository->all();
        $this->assertEquals($allCount - 1, $found_users->count());
        $this->assertFalse($found_users->contains($otherUser));

        $repository->modify()->addFilter('profile.is_human', '=true');
        $found_users = $repository->all();
        $this->assertEquals($found_users->count(), 3);

        $filters = $repository->modify()->getFilters();
        $this->assertEquals([
            'username'         => '~galaxyfarfaraway.com',
            'profile.is_human' => '=true',
        ], $filters);
    }

    public function testItCanSetFillable()
    {
        $repository = $this->getRepository(User::class);

        $this->assertSame(User::FILLABLE, $repository->accessControl()->getFillable());

        $repository->accessControl()->setFillable(['foo']);

        $this->assertSame(['foo'], $repository->accessControl()->getFillable());
    }

    public function testItCanAddFillable()
    {
        $repository = $this->getRepository(User::class);

        $this->assertSame(User::FILLABLE, $repository->accessControl()->getFillable());

        $repository->accessControl()->addFillable('foo');

        $expect = User::FILLABLE;
        $expect[] = 'foo';

        $this->assertSame($expect, $repository->accessControl()->getFillable());
    }

    public function testItCanAddManyFillable()
    {
        $repository = $this->getRepository(User::class);

        $this->assertSame(User::FILLABLE, $repository->accessControl()->getFillable());

        $repository->accessControl()->addManyFillable(['foo', 'bar', 'baz']);

        $expect = User::FILLABLE;
        $expect[] = 'foo';
        $expect[] = 'bar';
        $expect[] = 'baz';

        $this->assertSame($expect, $repository->accessControl()->getFillable());
    }

    public function testItCanRemoveFillable()
    {
        $repository = $this->getRepository(User::class);

        $this->assertSame(User::FILLABLE, $repository->accessControl()->getFillable());

        $repository->accessControl()->setFillable([
            'foo',
            'baz',
            'bag',
        ]);

        $this->assertSame([
            'foo',
            'baz',
            'bag',
        ], $repository->accessControl()->getFillable());

        $repository->accessControl()->removeFillable('baz');

        $this->assertSame(['foo', 'bag'], $repository->accessControl()->getFillable());
    }

    public function testItCanRemoveManyFillable()
    {
        $repository = $this->getRepository(User::class);

        $this->assertSame(User::FILLABLE, $repository->accessControl()->getFillable());

        $repository->accessControl()->setFillable([
            'foo',
            'baz',
            'bag',
        ]);

        $this->assertSame([
            'foo',
            'baz',
            'bag',
        ], $repository->accessControl()->getFillable());

        $repository->accessControl()->removeManyFillable(['baz', 'bag']);

        $this->assertSame(['foo'], $repository->accessControl()->getFillable());
    }

    public function testItCanDetermineIfIsFillable()
    {
        $repository = $this->getRepository(User::class);

        $this->assertSame(User::FILLABLE, $repository->accessControl()->getFillable());

        $repository->accessControl()->setFillable([
            '*' // allow all
        ]);

        $this->assertSame(AccessControl::ALLOW_ALL, $repository->accessControl()->getFillable());

        $this->assertTrue($repository->accessControl()->isFillable('foobar'));

        $repository->accessControl()->setFillable([
            'foo',
            'baz',
            'bag',
        ]);

        $this->assertFalse($repository->accessControl()->isFillable('foobar'));
        $this->assertTrue($repository->accessControl()->isFillable('foo'));
    }

    public function testItCanSetIncludable()
    {
        $repository = $this->getRepository(User::class);

        $this->assertSame(User::INCLUDABLE, $repository->accessControl()->getIncludable());

        $repository->accessControl()->setIncludable([
            'foo',
            'bar',
            'baz'
        ]);

        $this->assertSame(['foo', 'bar', 'baz'], $repository->accessControl()->getIncludable());
    }

    public function testItCanAddIncludable()
    {
        $repository = $this->getRepository(User::class);

        $this->assertSame(User::INCLUDABLE, $repository->accessControl()->getIncludable());

        $repository->accessControl()->setIncludable([
            'foo',
            'bar',
            'baz'
        ]);

        $repository->accessControl()->addIncludable('foobar');

        $this->assertSame(['foo', 'bar', 'baz', 'foobar'], $repository->accessControl()->getIncludable());
    }

    public function testItCanAddManyIncludable()
    {
        $repository = $this->getRepository(User::class);

        $this->assertSame(User::INCLUDABLE, $repository->accessControl()->getIncludable());

        $repository->accessControl()->setIncludable([
            'foo',
            'bar',
            'baz'
        ]);

        $repository->accessControl()->addManyIncludable(['foobar', 'bazbat']);

        $this->assertSame(['foo', 'bar', 'baz', 'foobar', 'bazbat'], $repository->accessControl()->getIncludable());
    }

    public function testItCanRemoveIncludable()
    {
        $repository = $this->getRepository(User::class);

        $this->assertSame(User::INCLUDABLE, $repository->accessControl()->getIncludable());

        $repository->accessControl()->setIncludable([
            'foo',
            'bar',
            'baz'
        ]);

        $repository->accessControl()->removeIncludable('foo');

        $this->assertSame(['bar', 'baz'], $repository->accessControl()->getIncludable());
    }

    public function testItCanRemoveManyIncludable()
    {
        $repository = $this->getRepository(User::class);

        $this->assertSame(User::INCLUDABLE, $repository->accessControl()->getIncludable());

        $repository->accessControl()->setIncludable([
            'foo',
            'bar',
            'baz'
        ]);

        $repository->accessControl()->removeManyIncludable(['foo', 'bar']);

        $this->assertSame(['baz'], $repository->accessControl()->getIncludable());
    }

    public function testItCanDetermineIsIncludable()
    {
        $repository = $this->getRepository(User::class);

        $this->assertSame(User::INCLUDABLE, $repository->accessControl()->getIncludable());

        $repository->accessControl()->setIncludable([
            '*' // Allow all
        ]);

        $this->assertSame(AccessControl::ALLOW_ALL, $repository->accessControl()->getIncludable());

        $this->assertTrue($repository->accessControl()->isIncludable('foobar'));

        $repository->accessControl()->setIncludable([
            'foo',
            'bar',
            'baz'
        ]);

        $this->assertFalse($repository->accessControl()->isIncludable('foobar'));
        $this->assertTrue($repository->accessControl()->isIncludable('foo'));
    }

    public function testItCanSetFilterable()
    {
        $repository = $this->getRepository(User::class);

        $this->assertSame(User::FILTERABLE, $repository->accessControl()->getFilterable());

        $repository->accessControl()->setFilterable([
            'foo',
            'bar',
            'baz',
        ]);

        $this->assertSame([
            'foo',
            'bar',
            'baz',
        ], $repository->accessControl()->getFilterable());
    }

    public function testItCanAddFilterable()
    {
        $repository = $this->getRepository(User::class);

        $this->assertSame(User::FILTERABLE, $repository->accessControl()->getFilterable());

        $repository->accessControl()->setFilterable([
            'foo',
            'bar',
            'baz',
        ]);

        $repository->accessControl()->addFilterable('foobar');

        $this->assertSame([
            'foo',
            'bar',
            'baz',
            'foobar',
        ], $repository->accessControl()->getFilterable());
    }

    public function testItCanAddManyFilterable()
    {
        $repository = $this->getRepository(User::class);

        $this->assertSame(User::FILTERABLE, $repository->accessControl()->getFilterable());

        $repository->accessControl()->setFilterable([
            'foo',
            'bar',
            'baz',
        ]);

        $repository->accessControl()->addManyFilterable(['foobar', 'bazbat']);

        $this->assertSame([
            'foo',
            'bar',
            'baz',
            'foobar',
            'bazbat',
        ], $repository->accessControl()->getFilterable());
    }

    public function testItCanRemoveFilterable()
    {
        $repository = $this->getRepository(User::class);

        $this->assertSame(User::FILTERABLE, $repository->accessControl()->getFilterable());

        $repository->accessControl()->setFilterable([
            'foo',
            'bar',
            'baz',
        ]);

        $repository->accessControl()->removeFilterable('bar');

        $this->assertSame(['foo', 'baz',], $repository->accessControl()->getFilterable());
    }

    public function testItCanRemoveManyFilterable()
    {
        $repository = $this->getRepository(User::class);

        $this->assertSame(User::FILTERABLE, $repository->accessControl()->getFilterable());

        $repository->accessControl()->setFilterable([
            'foo',
            'bar',
            'baz',
        ]);

        $repository->accessControl()->removeManyFilterable(['foo', 'baz']);

        $this->assertSame(['bar',], $repository->accessControl()->getFilterable());
    }

    public function testItCanDetermineIsFilterable()
    {
        $repository = $this->getRepository(User::class);

        $this->assertSame(User::FILTERABLE, $repository->accessControl()->getFilterable());

        $repository->accessControl()->setFilterable([
            '*'
        ]);

        $this->assertSame(AccessControl::ALLOW_ALL, $repository->accessControl()->getFilterable());

        $this->assertTrue($repository->accessControl()->isFilterable('foobar'));

        $repository->accessControl()->setFilterable([
            'foo',
            'bar',
            'baz',
        ]);

        $this->assertFalse($repository->accessControl()->isFilterable('foobar'));
        $this->assertTrue($repository->accessControl()->isFilterable('foo'));
    }

    public function testItDoesNotFillFieldThatIsNotFillable()
    {
        $post = $this->getRepository(
            Post::class,
            [
                'title' => 'All the Tags',
                'not_fillable' => 'should not be set',
                'user' => [
                    'username' => 'simon',
                    'not_fillable' => 'should not be set',
                    'profile' => [
                        'favorite_cheese' => 'brie',
                    ],
                ],
                'tags' => [
                    [
                        'label' => 'Important Stuff',
                        'not_fillable' => 'should not be set',
                    ],
                    [
                        'label' => 'Less Important Stuff',
                        'not_fillable' => 'should not be set',
                    ],
                ],
            ]
        )->save();

        $this->assertEquals($post->tags()->count(), 2);
        $this->assertNotNull($post->user->profile);
        $this->assertNotNull($post->user->profile->favorite_cheese, 'brie');

        $this->assertNull($post->not_fillable);
        $this->assertNull($post->user->not_fillable);
        $this->assertNull($post->tags->get(0)->not_fillable);
        $this->assertNull($post->tags->get(1)->not_fillable);
    }

    public function testItDoesNotIncludeRelationThatIsNotIncludable()
    {
        $this->getRepository(
            User::class,
            [
                'username' => 'joe',
                'posts' => [
                    [
                        'title' => 'Some Great Post',
                    ],
                ]
            ]
        )->save();

        $repository = $this->getRepository(User::class);
        $repository->modify()->setFilters(['username' => 'joe'])
            ->setEagerLoads(
                [
                    'posts.nothing',
                    'not_exists',
                    'not_includable'
                ]
            );
        $user = $repository->all()->first();

        $this->assertNotNull($user);
        $this->assertInstanceOf(Collection::class, $user->posts);
        $this->assertInstanceOf(Post::class, $user->posts->first());

        $user = $user->toArray();

        $this->assertTrue(!isset($user['posts'][0]['nothing']));
        $this->assertTrue(!isset($user['not_exists']));
        $this->assertTrue(!isset($user['not_includable']));
    }

    public function testItDoesNotFilterOnWhatIsNotFilterable()
    {
        $this->seedUsers();

        // Test that the repository implements filters correctly
        $repository = $this->getRepository(User::class);
        $this->assertEquals(User::count(), $repository->all()->count());

        $repository->modify()->setFilters([
            'not_filterable' => '=foo', // Should not be applied
            'posts.not_filterable' => '=foo', // Should not be applied
        ]);
        $found_users = $repository->all();
        $this->assertEquals(User::count(), $found_users->count()); // No filters applied, expect to get all 4 users
    }

    public function testItFiltersWithAllFieldsIfAllowAllIsSet()
    {
        $this->seedUsers();

        $repository = $this->getRepository(User::class);
        $this->assertEquals(User::count(), $repository->all()->count());

        // Filters not applied
        $repository->accessControl()->setFilterable([]);
        $repository->modify()->setFilters([
            'profile.is_human' => '=true',
            'times_captured' => '>2'
        ]);
        $found_users = $repository->all();

        $this->assertEquals(User::count(), $found_users->count());

        // Filters now applied
        $repository->accessControl()->setFilterable(AccessControl::ALLOW_ALL);
        $repository->modify()->setFilters([
            'profile.is_human' => '=true',
            'times_captured' => '>2'
        ]);
        $found_users = $repository->all();

        $this->assertEquals(
            User::query()
                ->whereHas('profile', function ($query) {
                    $query->where('is_human', true);
                })
                ->where('times_captured', '>', 2)
                ->count(),
            $found_users->count()
        );
    }

    public function testItCanGetIncludableAsAssoc()
    {
        $repository = $this->getRepository(User::class);

        $repository->accessControl()->setIncludable([
            'foo',
            'bar',
            'baz',
        ]);

        $this->assertSame([
            'foo' => true,
            'bar' => true,
            'baz' => true,
        ], $repository->accessControl()->getIncludable(true));
    }

    public function testItCanGetFillableAsAssoc()
    {
        $repository = $this->getRepository(User::class);

        $repository->accessControl()->setFillable([
            'foo',
            'bar',
            'baz',
        ]);

        $this->assertSame([
            'foo' => true,
            'bar' => true,
            'baz' => true,
        ], $repository->accessControl()->getFillable(true));
    }

    public function testItCanGetFilterableAsAssoc()
    {
        $repository = $this->getRepository(User::class);

        $repository->accessControl()->setFilterable([
            'foo',
            'bar',
            'baz',
        ]);

        $this->assertSame([
            'foo' => true,
            'bar' => true,
            'baz' => true,
        ], $repository->accessControl()->getFilterable(true));
    }

    public function testItCanAggregateQueryCount()
    {
        $this->markTestIncomplete();
    }

    public function testItCanAggregateQueryMin()
    {
        $this->markTestIncomplete();
    }

    public function testItCanAggregateQueryMax()
    {
        $this->markTestIncomplete();
    }

    public function testItCanAggregateQuerySum()
    {
        $this->markTestIncomplete();
    }

    public function testItCanAggregateQueryAverage()
    {
        $this->markTestIncomplete();
    }

    public function testItCanGroupQuery()
    {
        $this->markTestIncomplete();
    }

    /**
     * @test
     *
     * The repository can create many models given an array of items.
     */
    public function testItCanCreateMany()
    {
        $data = [
            ['username' => 'sue'],
            ['username' => 'dave'],
        ];

        $users = $this->getRepository(User::class, $data)->createMany();

        $this->assertInstanceOf(Collection::class, $users);
        $this->assertEquals($users->where('username', '=', 'sue')->first()->username, 'sue');
        $this->assertEquals($users->where('username', '=', 'dave')->first()->username, 'dave');
    }

    /**
     * @test
     *
     * The repository can update many models given an array of items with an id.
     */
    public function testItCanUpdateMany()
    {
        $userOne = $this->getRepository(User::class, ['username' => 'bobby'])->save();
        $userTwo = $this->getRepository(User::class, ['username' => 'sam'])->save();
        $this->assertEquals($userOne->getKey(), 1);
        $this->assertEquals($userOne->username, 'bobby');
        $this->assertEquals($userTwo->getKey(), 2);
        $this->assertEquals($userTwo->username, 'sam');

        $users = $this->getRepository(User::class, [
            ['id' => 1, 'username' => 'sue'],
            ['id' => 2, 'username' => 'dave'],
        ])->updateMany();

        $this->assertInstanceOf(Collection::class, $users);
        $this->assertEquals($users->find(1)->id, 1);
        $this->assertEquals($users->find(1)->username, 'sue');
        $this->assertEquals($users->find(2)->id, 2);
        $this->assertEquals($users->find(2)->username, 'dave');
    }

    /**
     * @test
     *
     * The repository can check if the input should be a many operation or not.
     */
    public function testItCanCheckIfManyOperation()
    {
        $notManyOperationData = ['id' => 1, 'username' => 'bobby'];
        $manyOperationData = [['id' => 1, 'username' => 'bobby'], ['id' => 2, 'username' => 'sam']];

        $this->assertFalse($this->getRepository(User::class, [])->isManyOperation());
        $this->assertFalse($this->getRepository(User::class, $notManyOperationData)->isManyOperation());
        $this->assertTrue($this->getRepository(User::class, $manyOperationData)->isManyOperation());
    }

    public function testItCanFindASimpleModelAndPickASubsetOfColumns()
    {
        $this->seedUsers();
        $repo = $this->getRepository(User::class);
        $user = $repo->findOrFail(1);

        $repo->modify()->addPicks(['id', 'username']);

        $userWithPicks = $repo->findOrFail(1);
        //Eloquent models that were loaded with a subset of columns will only have those attributes set
        $this->assertEquals(['id', 'username'], array_keys($userWithPicks->getAttributes()));


        $repo->modify()->addPick('occupation');

        $repo->modify()->setEagerLoads(['posts']);
        $userWithMorePicksAndEagerLoad = $repo->findOrFail(1);
        //Only picked columns yield attributes
        $this->assertEquals(['id', 'username', 'occupation'], array_keys($userWithMorePicksAndEagerLoad->getAttributes()));

        $this->assertEqualsCanonicalizing(Schema::getColumnListing($user->getTable()), array_keys($user->getAttributes()));

        //Assert that posts have been eager loaded in addition to picked attributes
        $this->assertArrayHasKey('posts', $userWithMorePicksAndEagerLoad->toArray());
        $this->assertNotEmpty($userWithMorePicksAndEagerLoad->posts);
    }

    public function testItCanFindASimpleModelWithAppendedAttributesAndPickASubsetOfAttributes()
    {
        $this->seedUsers();

        $repo = $this->getRepository(
            get_class(
                new class() extends User {
                    protected $visible = [
                        'id',
                        'username',
                        'name',
                        'hands',
                        'occupation',
                        'times_captured',
                        'posts',
                        'profile',
                        'foobar',
                        'barbaz',
                        'yarderp'
                    ];
                    protected $appends = ['foobar', 'barbaz'];
                    protected $with = ['yarderp'];
                    protected $hidden = ['stays_hidden'];

                    protected $table = 'users';

                    public function getFoobarAttribute()
                    {
                        return 123;
                    }

                    public function getBarbazAttribute()
                    {
                        return 456;
                    }

                    public function getStaysHiddenAttribute()
                    {
                        return false;
                    }

                    public function yarderp()
                    {
                        return $this->hasOne(Profile::class, 'user_id');
                    }
                }
            )
        );
        $repo->modify()->addPicks(['id', 'username', 'foobar', 'barbaz']);

        $userWithPicks = $repo->findOrFail(1);
        $this->assertEquals(['id', 'username', 'foobar', 'barbaz'], array_keys($userWithPicks->toArray()));

        $repo->modify()->setPicks(['id', 'username']);
        $userWithPicks = $repo->findOrFail(1);
        $this->assertEquals(['id', 'username'], array_keys($userWithPicks->toArray()));

        $repo->modify()->setPicks(['id', 'username', 'stay_hidden', 'yarderp']);
        $userWithPicks = $repo->findOrFail(1);
        $this->assertEquals(['id', 'username', 'yarderp'], array_keys($userWithPicks->toArray()));
    }

    public function testItCanFindAListOfModelsWithAppendedAttributesAndPickASubsetOfAttributes()
    {
        $this->seedUsers();

        $repo = $this->getRepository(
            get_class(
                new class() extends User {
                    protected $visible = [
                        'id',
                        'username',
                        'name',
                        'hands',
                        'occupation',
                        'times_captured',
                        'posts',
                        'profile',
                        'foobar',
                        'barbaz',
                        'yarderp'
                    ];
                    protected $appends = ['foobar', 'barbaz'];
                    protected $with = ['yarderp'];
                    protected $hidden = ['stays_hidden'];

                    protected $table = 'users';

                    public function getFoobarAttribute()
                    {
                        return 123;
                    }

                    public function getBarbazAttribute()
                    {
                        return 456;
                    }

                    public function getStaysHiddenAttribute()
                    {
                        return false;
                    }

                    public function yarderp()
                    {
                        return $this->hasOne(Profile::class, 'user_id');
                    }
                }
            )
        );
        $repo->modify()->addPicks(['id', 'username', 'foobar', 'barbaz']);

        $usersWithPicks = $repo->all();
        $this->assertTrue($usersWithPicks->every(fn ($userWithPicks) => ['id', 'username', 'foobar', 'barbaz'] === array_keys($userWithPicks->toArray())));

        $repo->modify()->setPicks(['id', 'username']);
        $usersWithPicks = $repo->all();
        $this->assertTrue($usersWithPicks->every(fn ($userWithPicks) => ['id', 'username'] === array_keys($userWithPicks->toArray())));

        $repo->modify()->setPicks(['id', 'username', 'stay_hidden', 'yarderp']);
        $usersWithPicks = $repo->all();
        $this->assertTrue($usersWithPicks->every(fn ($userWithPicks) => ['id', 'username', 'yarderp'] === array_keys($userWithPicks->toArray())));

        $paginatedUsersWithPicks = $repo->paginate(10);
        $this->assertTrue($paginatedUsersWithPicks->getCollection()->every(fn ($userWithPicks) => ['id', 'username', 'yarderp'] === array_keys($userWithPicks->toArray())));
    }

    public function testItShowsAllVisibleFieldsWhenNoPicksAreApplied()
    {
        $this->seedUsers();

        $repo = $this->getRepository(
            get_class(
                new class() extends User {
                    protected $visible = [
                        'id',
                        'username',
                        'name',
                        'hands',
                        'occupation',
                        'times_captured',
                        'posts',
                        'profile',
                        'foobar',
                        'barbaz',
                        'yarderp'
                    ];
                    protected $appends = ['foobar', 'barbaz'];
                    protected $with = ['yarderp'];
                    protected $hidden = ['stays_hidden'];

                    protected $table = 'users';

                    public function getFoobarAttribute()
                    {
                        return 123;
                    }

                    public function getBarbazAttribute()
                    {
                        return 456;
                    }

                    public function getStaysHiddenAttribute()
                    {
                        return false;
                    }

                    public function yarderp()
                    {
                        return $this->hasOne(Profile::class, 'user_id');
                    }

                    public function profile()
                    {
                        return $this->hasOne(Profile::class, 'user_id');
                    }
                }
            )
        );


        //All visible fields are present except lazy-loaded relationships
        $usersWithPicks = Collection::wrap([$repo->firstOrFail(), $repo->first(), $repo->find(1), $repo->findOrFail(1)])->concat($repo->all());
        $usersWithPicks->each(function ($userWithPicks) {
            $modelToArray = $userWithPicks->toArray();
            $modelKeys = array_keys($modelToArray);
            $diff = array_diff($userWithPicks->getVisible(), $modelKeys);
            $this->assertEqualsCanonicalizing($diff, ['posts', 'profile']);
        });

        $repo->modify()->setEagerLoads(['profile']);
        $usersWithPicks = Collection::wrap([$repo->firstOrFail(), $repo->first(), $repo->find(1), $repo->findOrFail(1)])->concat($repo->all());
        $usersWithPicks->each(fn ($userWithPicks) => $this->assertEqualsCanonicalizing(array_diff($userWithPicks->getVisible(), array_keys($userWithPicks->toArray())), ['posts']));
    }


    public function testItOnlyPicksASubsetOfColumnsThatExistOnTheResourceModel()
    {
        $this->seedUsers();
        $repo = $this->getRepository(User::class);
        $repo->modify()->setPicks(['id', 'username', 'notinthismodel']);

        $this->assertEqualsCanonicalizing(['id', 'username'], array_keys($repo->firstOrFail()->getAttributes()));
    }

    public function sortDirectionProvider()
    {
        return [['asc'], ['desc']];
    }

    protected function assertCollectionIsSorted(\Illuminate\Support\Collection $collection, string $direction = 'asc')
    {
        $valueToAvoid = $direction == 'desc' ? -1 : 1;
        $collection->sliding(2)->eachSpread(function ($previous, $current) use ($valueToAvoid, $direction) {
            $word = $valueToAvoid == -1 ? 'after' : 'before';
            $message = "Failed asserting that the collection is sorted in $direction order. $previous does not come $word to $current.";
            $this->assertNotEquals($valueToAvoid, $previous <=> $current, $message);
        });
    }
}
