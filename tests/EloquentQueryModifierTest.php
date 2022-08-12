<?php

namespace Koala\Pouch\Tests;

use Koala\Pouch\EloquentQueryModifier;

class EloquentQueryModifierTest extends TestCase
{
    public function testItAssignmentOfPicksIsReflected()
    {
        $modifier = new EloquentQueryModifier();

        $modifier->setPicks(['foo', 'bar']);
        $this->assertEquals(['foo', 'bar'], $modifier->getPicks());

        $modifier->setPicks([]);
        $this->assertEquals([], $modifier->getPicks());

        $modifier->addPick('foo');
        $this->assertEquals(['foo'], $modifier->getPicks());

        $modifier->addPick('');
        $this->assertEquals(['foo'], $modifier->getPicks());

        $modifier->addPick('bar');
        $this->assertEquals(['foo', 'bar'], $modifier->getPicks());

        $modifier->addPicks(['foo', 'bar', 'bar', 'bar', 'foo', 'baz']);
        $this->assertEquals(['foo', 'bar', 'baz'], $modifier->getPicks());

        $modifier->addPicks(['foo', 'bar', 'baz', 'bar', 'foo', 'baz']);
        $this->assertEquals(['foo', 'bar', 'baz'], $modifier->getPicks());
    }
}
