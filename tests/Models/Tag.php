<?php

namespace Koala\Pouch\Tests\Models;

use Koala\Pouch\Contracts\MagicBoxResource;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model implements MagicBoxResource
{
    /**
     * @const array
     */
    public const FILLABLE = [
        'label',
        'posts',
    ];

    /**
     * @const array
     */
    public const INCLUDABLE = ['user', 'posts'];

    /**
     * @const array
     */
    public const FILTERABLE = [
        'username',
        'name',
        'hands',
        'occupation',
        'times_captured',
        'posts.label',
    ];

    /**
     * @var string
     */
    protected $table = 'tags';

    /**
     * @var array
     */
    protected $fillable = [
        'label',
        'posts',
    ];

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function posts()
    {
        return $this->belongsToMany(Post::class)->withPivot('extra');
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
