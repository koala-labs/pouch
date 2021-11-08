<?php

namespace Koala\Pouch\Tests\Models;

use Koala\Pouch\Contracts\PouchResource;
use Illuminate\Database\Eloquent\Model;

class NotIncludable extends Model implements PouchResource
{
	/**
	 * @const array
	 */
	const FILLABLE = [];

	/**
	 * @const array
	 */
	const INCLUDABLE = [];

	/**
	 * @const array
	 */
	const FILTERABLE = [];

	/**
	 * @var string
	 */
	protected $table = 'not_includable';

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
