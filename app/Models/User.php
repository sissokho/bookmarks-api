<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, \Staudenmeir\EloquentHasManyDeep\HasRelationships;

    /**
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return HasMany<Bookmark>
     */
    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    /**
     * Undocumented function
     *
     * @return HasManyDeep<Tag>
     */
    public function tags(): HasManyDeep
    {
        return $this->hasManyDeep(Tag::class, [Bookmark::class, 'bookmark_tag']);
    }
}
