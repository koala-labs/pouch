<?php
namespace Koala\Pouch\Tests\Seeds;

use Koala\Pouch\Tests\Models\Post;
use Koala\Pouch\Tests\Models\Profile;
use Koala\Pouch\Tests\Models\Tag;
use Koala\Pouch\Tests\Models\User;
use Illuminate\Database\Seeder;
use Symfony\Component\Console\Helper\Table;

class FilterDataSeeder extends Seeder
{
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run()
	{
		foreach ($this->users() as $user) {
			$user_instance = new User;

			foreach (
				[
					'username',
					'name',
					'hands',
					'times_captured',
					'occupation',
				] as $attribute
			) {
				$user_instance->{$attribute} = $user[$attribute];
			}

			$user_instance->save();

            if (isset($user['profile'])) {
                $profile = new Profile;
                foreach ($user['profile'] as $key => $value) {
                    $profile->{$key} = $value;
                }
                $profile->user_id = $user_instance->id;
                $profile->save();
            }


            if (isset($user['posts'])) {
                foreach ($user['posts'] as $post) {
                    $post_instance          = new Post;
                    $post_instance->title   = $post['title'];
                    $post_instance->user_id = $user_instance->id;
                    $post_instance->save();

                    $tag_ids = [];
                    foreach ($post['tags'] as $tag) {
                        $tag_instance = Tag::firstOrCreate(
                            ['label' => $tag['label']],
                            [
                                'label' => $tag['label']
                            ]
                        );

                        $tag_ids[] = $tag_instance->id;
                    }

                    $post_instance->tags()->sync($tag_ids);
                }
            }
		}

		$users = User::with(
			[
				'profile',
				'posts.tags'
			]
		)->get()->toArray();
		$test  = 'test';
	}

	public function users()
	{
		return [
			[
				'username'       => 'lskywalker@galaxyfarfaraway.com',
				'name'           => 'Luke Skywalker',
				'hands'          => 1,
				'times_captured' => 4,
				'occupation'     => 'Jedi',
				'profile'        => [
					'favorite_cheese' => 'Gouda',
					'favorite_fruit'  => 'Apples',
					'is_human'        => true
				],
				'posts'          => [
					[
						'title' => 'I Kissed a Princess and I Liked it',
						'tags'  => [
							['label' => '#peace',],
							['label' => '#thelastjedi',]
						]
					]
				]
			],
			[
				'username'       => 'lorgana@galaxyfarfaraway.com',
				'name'           => 'Leia Organa',
				'hands'          => 2,
				'times_captured' => 6,
				'occupation'     => null,
				'profile'        => [
					'favorite_cheese' => 'Provolone',
					'favorite_fruit'  => 'Mystery Berries',
					'is_human'        => true
				],
				'posts'          => [
					[
						'title' => 'Smugglers: A Girl\'s Dream',
						'tags'  => [
							['label' => '#princess',],
							['label' => '#mysonistheworst',],
						]
					]
				]
			],
			[
				'username'       => 'solocup@galaxyfarfaraway.com',
				'name'           => 'Han Solo',
				'hands'          => 2,
				'times_captured' => 1,
				'occupation'     => 'Smuggler',
				'profile'        => [
					'favorite_cheese' => 'Cheddar',
					'favorite_fruit'  => null,
					'is_human'        => true
				],
				'posts'          => [
					[
						'title' => '10 Easy Ways to Clean Fur From Couches',
						'tags'  => [
							['label' => '#iknow',],
							['label' => '#triggerfinger',],
							['label' => '#mysonistheworst',],
						]
					],
                    [
                        'title' => '99 Problems But A Hutt Ain\'t One',
                        'tags' => [
                            ['label' => '#og'],
                            ['label' => '#99']
                        ]
                    ]
				]
			],
			[
				'username'       => 'chewbaclava@galaxyfarfaraway.com',
				'name'           => 'Chewbacca',
				'hands'          => 0,
				'times_captured' => 0,
				'occupation'     => 'Smuggler\'s Assistant',
				'profile'        => [
					'favorite_cheese' => 'brie',
					'favorite_fruit'  => null,
					'is_human'        => false
				],
				'posts'          => [
					[
						'title' => 'Rrrrrrr-ghghg Rrrr-ghghghghgh Rrrr-ghghghgh!',
						'tags'  => [
							['label' => '#starwarsfurlife',],
							['label' => '#chewonthis',],
						]
					]
				]
			],
            [
                'username'       => 'huttboss@galaxyfarfaraway.com',
                'name'           => 'Jabba The Hutt',
                'hands'          => 2,
                'times_captured' => 0,
                'occupation'     => 'Crime boss',
                'profile'        => [
                    'favorite_cheese' => 'brie',
                    'favorite_fruit'  => 'apple',
                    'is_human'        => false
                ],
                'posts'          => [
                    [
                        'title' => 'How To Feed And Care For Your Sarlacc',
                        'tags'  => [
                            ['label' => '#hungry',],
                            ['label' => '#tatooine',],
                            ['label' => '#og',],
                        ]
                    ],
                    [
                        'title' => 'Fu-shu-ka-me-na-wa-Han-Solo-ha-ha-ha!',
                        'tags'  => [
                            ['label' => '#princess',],
                            ['label' => '#og',],
                        ]
                    ],
                    [
                        'title' => 'How To Make Friends And Influence People',
                        'tags'  => [
                            ['label' => '#friends',],
                            ['label' => '#og',],
                        ]
                    ]
                ]
            ]
		];
	}
}
