<?php

declare(strict_types=1);

namespace App\V2\Products\Domain\Enums;

enum ProductSortType: string
{
    case PRICE_ASC = 'price_asc';
    case PRICE_DESC = 'price_desc';
    case RATING_DESC = 'rating_desc';
    case NEWEST = 'newest';

    public static function default(): self
    {
        return self::NEWEST;
    }

    public function toOrderBy(): array
    {
        return match ($this) {
            self::PRICE_ASC => ['price', 'asc'],
            self::PRICE_DESC => ['price', 'desc'],
            self::RATING_DESC => ['rating', 'desc'],
            self::NEWEST => ['created_at', 'desc'],
        };
    }
}
