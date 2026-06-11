<?php

declare(strict_types=1);

namespace Jessecruz\SimpleBlog\Tests\Stubs;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Jessecruz\SimpleBlog\Contracts\Author;

final class User extends Authenticatable implements Author
{
    protected $table = 'users';

    protected $guarded = [];

    public $timestamps = false;

    public function getBlogAuthorName(): string
    {
        return $this->name ?? 'Equipe';
    }

    public function getBlogAuthorInitials(): string
    {
        $words = preg_split('/\s+/', trim($this->getBlogAuthorName())) ?: [];
        $initials = array_map(
            fn (string $w) => mb_strtoupper(mb_substr($w, 0, 1)),
            array_slice($words, 0, 2),
        );

        return implode('', $initials);
    }

    public function getBlogAuthorAvatarUrl(): ?string
    {
        return $this->avatar_url ?? null;
    }
}
