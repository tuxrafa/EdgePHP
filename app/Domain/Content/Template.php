<?php

namespace App\Domain\Content;

use MongoDB\Laravel\Eloquent\Model;

class Template extends Model
{
    protected $connection = 'mongodb';

    protected $table = 'templates';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        '_id',
        'siteId',
        'name',
        'slug',
        'description',
        'status',
        'draftRevisionId',
        'publishedRevisionId',
        'metadata',
    ];

    public function draftRevision()
    {
        return $this->belongsTo(TemplateRevision::class, 'draftRevisionId', '_id');
    }

    public function publishedRevision()
    {
        return $this->belongsTo(TemplateRevision::class, 'publishedRevisionId', '_id');
    }
}
