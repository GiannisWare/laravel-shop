<?php

namespace App\Enums;

enum NavigationDestination: string
{
    case Home = 'home';
    case Catalogue = 'catalogue';
    case Category = 'category';
    case InternalPath = 'internal_path';
    case ExternalUrl = 'external_url';

    public function label(): string
    {
        return match ($this) {
            self::Home => 'Home page',
            self::Catalogue => 'All products',
            self::Category => 'Product category',
            self::InternalPath => 'Internal path',
            self::ExternalUrl => 'External website',
        };
    }
}
