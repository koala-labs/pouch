<?php

namespace Koala\Pouch\Tests\Models;

use Koala\Pouch\Contracts\PouchResource;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model implements PouchResource
{
    /**
     * @const array
     */
    public const FILLABLE = [
        'user_id',
        'favorite_cheese',
        'favorite_fruit',
        'is_human',
        'user',
    ];

    /**
     * @const array
     */
    public const INCLUDABLE = ['user',];

    /**
     * @const array
     */
    public const FILTERABLE = [
        'user_id',
        'favorite_cheese',
        'favorite_fruit',
        'is_human',
    ];

    protected $casts = [
        'is_human' => 'boolean'
    ];

    /**
     * @var string
     */
    protected $table = 'profiles';

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
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
