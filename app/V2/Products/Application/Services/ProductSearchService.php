<?php

declare(strict_types=1);

namespace App\V2\Products\Application\Services;

use App\V2\Products\Application\DTO\ProductFilterDTO;
use App\V2\Products\Domain\Contracts\ProductSearchInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class ProductSearchService
{
    /** @var ProductSearchInterface[] */
    private array $drivers;

    public function __construct(ProductSearchInterface ...$drivers)
    {
        $this->drivers = $drivers;
    }

    public function search(ProductFilterDTO $filter): LengthAwarePaginator
    {
        $lastException = null;

        foreach ($this->drivers as $driver) {
            if (!$driver->isAvailable()) {
                continue;
            }

            try {
                return $driver->search($filter);
            } catch (\Throwable $e) {
                $lastException = $e;

                Log::error('Search engine failed, switching to fallback', [
                    'driver' => get_class($driver),
                    'exception' => $e->getMessage(),
                ]);

                continue;
            }
        }

        foreach ($this->drivers as $driver) {
            try {
                Log::warning('Falling back to database search');
                return $driver->search($filter);
            } catch (\Throwable $e) {
                $lastException = $e;
                continue;
            }
        }

        throw new \RuntimeException(
            'All search drivers failed',
            0,
            $lastException
        );
    }
}
