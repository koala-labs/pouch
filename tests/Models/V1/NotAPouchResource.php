<?php

namespace Koala\Pouch\Tests\Models\V1;

use Illuminate\Database\Eloquent\Model;

class NotAPouchResource extends Model
{
    /**
     * @const array
     */
    public const FILLABLE = [];

    /**
     * @const array
     */
    public const INCLUDABLE = [];

    /**
     * @const array
     */
    public const FILTERABLE = [];

    /**
     * @var string
     */
    protected $table = 'users';
}
