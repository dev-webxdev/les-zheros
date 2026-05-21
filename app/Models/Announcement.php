<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable([
    'user_id',
    'title',
    'tag',
    'excerpt',
    'content',
    'status',
    'published_at',
])]
class Announcement extends Model
{
    use SoftDeletes;

    public const TAGS = [
        'info' => 'Info',
        'event' => 'Event',
        'priority' => 'Prioritaire',
        'logistique' => 'Organisation',
        'maintenance' => 'Maintenance',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tagLabel(): string
    {
        return self::TAGS[$this->tag] ?? ucfirst($this->tag);
    }

    public function statusLabel(): string
    {
        if ($this->status === 'scheduled' && $this->published_at?->lte(now())) {
            return 'Publié';
        }

        return match ($this->status) {
            'published' => 'Publié',
            'scheduled' => 'Programmé',
            default => 'Brouillon',
        };
    }

    public function statusForForm(): string
    {
        if ($this->status === 'scheduled' && $this->published_at?->lte(now())) {
            return 'published';
        }

        return $this->status ?: 'draft';
    }

    public function statusTagClass(): string
    {
        if ($this->status === 'scheduled' && $this->published_at?->lte(now())) {
            return 'admin-tag--success';
        }

        return match ($this->status) {
            'published' => 'admin-tag--success',
            'scheduled' => 'admin-tag--primary',
            default => '',
        };
    }

    public function preview(): string
    {
        return Str::limit($this->plainContent(), 120);
    }

    public function hasReadMore(): bool
    {
        $plainContent = $this->plainContent();

        return Str::length($plainContent) > 120
            || preg_match('/<(ul|ol|li|blockquote|h[1-6]|table|figure)\b/i', (string) $this->content) === 1
            || count(array_filter(preg_split('/\R{2,}/', trim($this->decodedContent())) ?: [])) > 1;
    }

    public function formattedContent(): string
    {
        $content = trim($this->decodedContent());

        if ($content === '') {
            return '';
        }

        if (preg_match('/<(p|ul|ol|li|blockquote|h[1-6]|div|table|figure)\b/i', $content)) {
            return $content;
        }

        if (str_contains($content, '<')) {
            return '<p>'.$content.'</p>';
        }

        return collect(preg_split('/\R{2,}/', $content) ?: [])
            ->map(fn (string $paragraph): string => trim($paragraph))
            ->filter()
            ->map(fn (string $paragraph): string => '<p>'.nl2br(e($paragraph), false).'</p>')
            ->join('');
    }

    private function plainContent(): string
    {
        return trim(preg_replace('/\s+/', ' ', strip_tags($this->decodedContent())) ?? '');
    }

    private function decodedContent(): string
    {
        return str_replace(
            "\xc2\xa0",
            ' ',
            html_entity_decode((string) $this->content, ENT_QUOTES | ENT_HTML5, 'UTF-8')
        );
    }
}
