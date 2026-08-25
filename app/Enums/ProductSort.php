<?php

namespace App\Enums;

enum ProductSort: string
{
    case Recommended = 'recommended';
    case NameDescending = 'name_desc';
    case Newest = 'newest';

    public function label(): string
    {
        return match ($this) {
            self::Recommended => 'Recommended',
            self::NameDescending => 'Name: Z–A',
            self::Newest => 'Newest first',
        };
    }
}
