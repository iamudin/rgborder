<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ContentPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'category',
        'excerpt',
        'content',
        'featured_image',
        'is_published',
        'meta_title',
        'meta_description',
        'sort_order',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (ContentPage $page) {
            if (empty($page->slug)) {
                $page->slug = Str::slug($page->title);
            }
        });
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('created_at', 'desc');
    }

    public function getUrlAttribute(): string
    {
        return route('pages.show', $this->slug);
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'rules' => 'Aturan & Panduan',
            'facilities' => 'Fasilitas Tempur',
            'policies' => 'Kebijakan & Privasi',
            'faq' => 'Tanya Jawab',
            default => 'Informasi Umum',
        };
    }

    public function getCategoryBadgeColorAttribute(): string
    {
        return match ($this->category) {
            'rules' => 'amber',
            'facilities' => 'cyan',
            'policies' => 'purple',
            'faq' => 'blue',
            default => 'slate',
        };
    }
}
