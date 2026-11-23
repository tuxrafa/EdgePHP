<?php

namespace App\Domain\Content;

use MongoDB\Laravel\Eloquent\Model;

/**
 * Materialized reverse-index of content dependencies.
 * Source of truth remains the editorial documents; this speeds reverse lookups.
 */
class ContentReference extends Model
{
    protected $connection = 'mongodb';

    protected $table = 'content_references';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        '_id',
        'siteId',
        'fromType',
        'fromId',
        'fromRevisionId',
        'toType',
        'toId',
        'toVariation',
        'mode',
        'pinnedRevisionId',
        'path',
    ];
}
