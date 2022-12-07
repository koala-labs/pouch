<?php

namespace Koala\Pouch\Tests\Models;

use Koala\Pouch\Contracts\PouchResource;
use Illuminate\Database\Eloquent\Model;

class Reaction extends Model implements PouchResource
{
    /**
     * @const array
     */
    public const FILLABLE = [
        'name',
        'icon',
        'comment',
        'post_id'
    ];

    /**
     * @const array
     */
    public const INCLUDABLE = [
        'post',
    ];

    /**
     * @const array
     */
    public const FILTERABLE = [
        'name',
        'icon',
        'comment'
    ];

    /**
     * @var string
     */

    protected $fillable = self::FILLABLE;

    protected $hidden = [
        'laravel_through_key'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function post()
    {
        return $this->HasOne(Post::class);
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
