<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bookmark extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'favorite' => 'boolean',
    ];

    /**
     * @return BelongsToMany<Tag>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * @return BelongsTo<User, Bookmark>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function archive(): bool|null
    {
        return $this->delete();
    }

    /**
     * @param  Builder<Bookmark>  $query
     */
    public function scopeSearch(Builder $query, string $searchTerm): void
    {
        $query->where('title', 'like', "%{$searchTerm}%")
            ->orWhere('url', 'like', "%{$searchTerm}%");
    }
}
