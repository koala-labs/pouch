<?php

namespace Koala\Pouch\Tests\Models;

use Koala\Pouch\Contracts\PouchResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Model implements PouchResource
{
    use SoftDeletes;

    /**
     * @const array
     */
    public const FILLABLE = [
        'username',
        'name',
        'hands',
        'occupation',
        'times_captured',
        'posts',
        'profile',
        'reactions'
    ];

    /**
     * @const array
     */
    public const INCLUDABLE = [
        'posts',
        'posts.user',
        'profile',
    ];

    /**
     * @const array
     */
    public const FILTERABLE = [
        'username',
        'name',
        'hands',
        'occupation',
        'times_captured',
        'posts.title',
        'posts.tags',
        'posts.tags.label',
        'profile.is_human',
        'reactions.name',
        'reactions.icon',
        'reactions.comment'
    ];

    protected $casts = [
        'hands'          => 'integer',
        'times_captured' => 'integer'
    ];

    /**
     * @var string
     */
    protected $table = 'users';

    protected $fillable = self::FILLABLE;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function reactions()
    {
        return $this->hasManyThrough(Reaction::class, Post::class);
    }

    /**
     * For unit testing purposes
     *
     * @return array
     */
    public function getFillable()
    {
        return $this->fillable;
    }

    /**
     * For unit testing purposes
     *
     * @param array $fillable
     *
     * @return $this
     */
    public function setFillable(array $fillable)
    {
        $this->fillable = $fillable;

        return $this;
    }

    /**
     * Get the list of fields fillable by the repository
     *
     * @return array
     */
    public function getRepositoryFillable(): array
    {
        return self::FILLABLE;
    }

    /**
     * Get the list of relationships fillable by the repository
     *
     * @return array
     */
    public function getRepositoryIncludable(): array
    {
        return self::INCLUDABLE;
    }

    /**
     * Get the list of fields filterable by the repository
     *
     * @return array
     */
    public function getRepositoryFilterable(): array
    {
        return self::FILTERABLE;
    }
}
