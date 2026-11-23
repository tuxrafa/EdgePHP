<?php

namespace App\Domain\Content;

use MongoDB\Laravel\Eloquent\Model;

class FragmentRevision extends Model
{
    protected $connection = 'mongodb';

    protected $table = 'fragment_revisions';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        '_id',
        'fragmentId',
        'variation',
        'number',
        'content',
        'authorId',
        'message',
    ];

    protected $casts = [
        'number' => 'integer',
    ];

    public function fragment()
    {
        return $this->belongsTo(Fragment::class, 'fragmentId', '_id');
    }
}
