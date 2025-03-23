<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Adapters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Relaticle\Flowforge\Config\KanbanConfig;
use Relaticle\Flowforge\Contracts\KanbanAdapterInterface;

/**
 * Factory for creating Kanban adapters.
 *
 * This factory automatically creates the appropriate adapter based on the subject type
 * (model class, query builder, or relation) and configuration.
 */
class KanbanAdapterFactory
{
    /**
     * Create a Kanban adapter for the given subject and configuration.
     *
     * @param string|Builder|Relation $subject The subject to create the adapter for
     * @param KanbanConfig $config The Kanban configuration
     * @return KanbanAdapterInterface The created adapter
     * 
     * @throws \InvalidArgumentException If the subject is not a valid type
     */
    public static function create(
        string|Builder|Relation $subject,
        KanbanConfig $config
    ): KanbanAdapterInterface {
        // If a model class string is passed
        if (is_string($subject)) {
            // Check if the class exists and is a subclass of Model
            if (!class_exists($subject) || !is_subclass_of($subject, Model::class)) {
                throw new \InvalidArgumentException(
                    "Subject must be a valid Eloquent model class, but {$subject} was given."
                );
            }
            
            return new EloquentModelAdapter($subject, $config);
        }
        
        // If a relation is passed, convert it to a query builder
        if ($subject instanceof Relation) {
            $subject = $subject->getQuery();
        }
        
        // At this point, we should have a query builder
        if (!($subject instanceof Builder)) {
            throw new \InvalidArgumentException(
                'Subject must be a model class, query builder, or relation.'
            );
        }
        
        return new EloquentQueryAdapter($subject, $config);
    }
} 