<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use League\CommonMark\CommonMarkConverter;

class EmailTemplate extends Model
{
    protected $fillable = ['name', 'event_type', 'subject', 'body_md'];

    public function scopeForEvent(Builder $query, string $eventType): Builder
    {
        return $query->where('event_type', $eventType);
    }

    public function renderSubject(array $vars): string
    {
        return str_replace(array_keys($vars), array_values($vars), $this->subject);
    }

    public function renderBody(array $vars): string
    {
        $body = str_replace(array_keys($vars), array_values($vars), $this->body_md);
        $converter = new CommonMarkConverter();
        return $converter->convert($body)->getContent();
    }
}
