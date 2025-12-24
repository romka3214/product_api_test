<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MeilisearchInit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:meilisearch-init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $client = app(\Meilisearch\Client::class);
        $params = [
            'id',
            'name',
            'price',
            'rating',
            'in_stock',
            'category_id',
            'created_at'
        ];
        $index = $client->index('local_products');
        $index->deleteAllDocuments();
        $index->updateSettings([
            'sortableAttributes' => $params,
            'filterableAttributes' => $params,
        ]);
    }
}
