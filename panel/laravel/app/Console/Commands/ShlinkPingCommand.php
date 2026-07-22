<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Shlink\ShlinkApiException;
use App\Support\Shlink\ShlinkClient;
use App\Support\Shlink\ShlinkException;
use Illuminate\Console\Command;

class ShlinkPingCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'shlink:ping';

    /**
     * @var string
     */
    protected $description = 'Verifica a conectividade com o motor Shlink (GET /short-urls?itemsPerPage=1).';

    public function handle(ShlinkClient $client): int
    {
        $baseUrl = (string) config('shlink.base_url');
        $apiVersion = (int) config('shlink.api_version', 3);

        $this->line(sprintf('Ping Shlink em %s (rest/v%d)...', $baseUrl, $apiVersion));

        try {
            $response = $client->request('GET', '/short-urls', ['itemsPerPage' => 1]);
        } catch (ShlinkApiException $e) {
            $this->error(sprintf('Falha na API Shlink [HTTP %d]: %s', $e->getStatusCode(), $e->getMessage()));

            return self::FAILURE;
        } catch (ShlinkException $e) {
            $this->error('Erro de transporte/config: '.$e->getMessage());

            return self::FAILURE;
        }

        $total = (int) ($response['shortUrls']['pagination']['totalItems'] ?? 0);
        $this->info(sprintf('OK. Shlink respondeu (short-urls totais: %d).', $total));

        return self::SUCCESS;
    }
}
