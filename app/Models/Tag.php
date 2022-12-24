<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Str;

class Tag extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $guarded = [];

    protected static function booted(): void
    {
        static::saving(function (Tag $tag) {
            $tag->slug = Str::slug($tag->name);
        });
    }

    /**
     * @return BelongsTo<User, Tag>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
