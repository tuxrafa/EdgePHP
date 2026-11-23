<?php

namespace App\Domain\Content;

use MongoDB\Laravel\Eloquent\Model;

class TemplateRevision extends Model
{
    protected $connection = 'mongodb';

    protected $table = 'template_revisions';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        '_id',
        'templateId',
        'number',
        'layout',
        'initialContent',
        'policies',
        'authorId',
        'message',
    ];

    protected $casts = [
        'number' => 'integer',
    ];

    public function template()
    {
        return $this->belongsTo(Template::class, 'templateId', '_id');
    }
}
