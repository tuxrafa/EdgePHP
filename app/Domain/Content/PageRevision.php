<?php

namespace App\Domain\Content;

use MongoDB\Laravel\Eloquent\Model;

class PageRevision extends Model
{
    protected $connection = 'mongodb';

    protected $table = 'page_revisions';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        '_id',
        'pageId',
        'number',
        'content',
        'authorId',
        'message',
    ];

    protected $casts = [
        'number' => 'integer',
    ];

    public function page()
    {
        return $this->belongsTo(Page::class, 'pageId', '_id');
    }
}
