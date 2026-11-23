<?php

namespace App\Domain\Content;

use MongoDB\Laravel\Eloquent\Model;

class Fragment extends Model
{
    protected $connection = 'mongodb';

    protected $table = 'fragments';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        '_id',
        'siteId',
        'locale',
        'name',
        'slug',
        'type',
        'status',
        'category',
        'tags',
        'defaultVariation',
        'draftRevisionId',
        'publishedRevisionId',
        'metadata',
    ];

    public function draftRevision()
    {
        return $this->belongsTo(FragmentRevision::class, 'draftRevisionId', '_id');
    }

    public function publishedRevision()
    {
        return $this->belongsTo(FragmentRevision::class, 'publishedRevisionId', '_id');
    }
}
