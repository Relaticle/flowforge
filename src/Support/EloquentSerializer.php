<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Utility class for serializing and deserializing Eloquent queries.
 */
class EloquentSerializer
{
    /**
     * Serialize an Eloquent query builder.
     *
     * @param  Builder  $query  The query to serialize
     * @return string The serialized query
     */
    public function serialize(Builder $query): string
    {
        return base64_encode(serialize($query));
    }

    /**
     * Unserialize an Eloquent query builder.
     *
     * @param  string  $serialized  The serialized query
     * @return Builder The unserialized query
     */
    public function unserialize(string $serialized): Builder
    {
        return unserialize(base64_decode($serialized));
    }
}
