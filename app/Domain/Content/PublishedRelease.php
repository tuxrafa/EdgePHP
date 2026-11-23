<?php

namespace App\Domain\Content;

use MongoDB\Laravel\Eloquent\Model;

/**
 * Immutable published composition snapshot for a page (or batch).
 */
class PublishedRelease extends Model
{
    protected $connection = 'mongodb';

    protected $table = 'published_releases';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        '_id',
        'siteId',
        'pageId',
        'pageRevisionId',
        'templateId',
        'templateRevisionId',
        'fragmentRevisions',
        'resolvedContent',
        'status',
        'message',
        'activatedAt',
    ];
}
