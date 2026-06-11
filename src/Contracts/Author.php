<?php

declare(strict_types=1);

namespace Jessecruz\SimpleBlog\Contracts;

interface Author
{
    public function getBlogAuthorName(): string;

    public function getBlogAuthorInitials(): string;

    public function getBlogAuthorAvatarUrl(): ?string;
}
