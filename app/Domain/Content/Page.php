<?php

namespace App\Domain\Content;

use MongoDB\Laravel\Eloquent\Model;

class Page extends Model
{
    protected $connection = 'mongodb';

    protected $table = 'pages';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        '_id',
        'siteId',
        'locale',
        'path',
        'title',
        'status',
        'templateId',
        'draftRevisionId',
        'publishedRevisionId',
        'publishedReleaseId',
        'metadata',
    ];

    public function draftRevision()
    {
        return $this->belongsTo(PageRevision::class, 'draftRevisionId', '_id');
    }

    public function publishedRevision()
    {
        return $this->belongsTo(PageRevision::class, 'publishedRevisionId', '_id');
    }
}
