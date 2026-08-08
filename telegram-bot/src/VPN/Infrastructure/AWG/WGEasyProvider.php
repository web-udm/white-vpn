<?php

declare(strict_types=1);

namespace App\VPN\Infrastructure\AWG;

use App\VPN\Domain\AWGProviderInterface;
use App\VPN\Port\VPNException;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Адаптер к панели wg-easy v15 (AmneziaWG включён через EXPERIMENTAL_AWG).
 * Авторизация — Basic Auth теми же учётными данными, что и в веб-интерфейсе.
 */
final readonly class WGEasyProvider implements AWGProviderInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        #[Autowire('%awg.base_url%')] private string $baseUrl,
        #[Autowire('%awg.username%')] private string $username,
        #[Autowire('%awg.password%')] private string $password,
    ) {
    }

    public function createPeer(string $name): string
    {
        $response = $this->request('POST', '/api/client', [
            'json' => ['name' => $name, 'expiresAt' => null],
        ]);

        $clientId = $response['clientId'] ?? null;

        if (!is_int($clientId) && !is_string($clientId)) {
            throw new VPNException('wg-easy createPeer: no clientId in response for peer ' . $name);
        }

        return (string) $clientId;
    }

    public function getPeerConfig(string $peerId): string
    {
        return $this->requestRaw('GET', '/api/client/' . rawurlencode($peerId) . '/configuration');
    }

    public function generateQrPngFromConfig(string $configContent): string
    {
        return new Builder(
            writer: new PngWriter(),
            data: $configContent,
            size: 400,
            margin: 10,
        )->build()->getString();
    }

    public function deletePeer(string $peerId): void
    {
        $this->request('DELETE', '/api/client/' . rawurlencode($peerId));
    }

    /** @return array<array{id: string, name: string}> */
    public function listPeers(): array
    {
        $response = $this->request('GET', '/api/client');

        return array_map(static function (mixed $peer): array {
            if (!is_array($peer)) {
                throw new VPNException('wg-easy listPeers: unexpected peer format');
            }

            return [
                'id'   => (string) ($peer['id'] ?? ''),
                'name' => (string) ($peer['name'] ?? ''),
            ];
        }, array_values($response));
    }

    /**
     * @param array<string, mixed> $options
     * @return array<mixed>
     */
    private function request(string $method, string $path, array $options = []): array
    {
        try {
            $response = $this->doRequest($method, $path, $options);
            $this->assertSuccessful($response, $path);

            return $response->toArray(false);
        } catch (VPNException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new VPNException('wg-easy request failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /** @param array<string, mixed> $options */
    private function requestRaw(string $method, string $path, array $options = []): string
    {
        try {
            $response = $this->doRequest($method, $path, $options);
            $this->assertSuccessful($response, $path);

            return $response->getContent(false);
        } catch (VPNException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new VPNException('wg-easy request failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /** @param array<string, mixed> $options */
    private function doRequest(string $method, string $path, array $options): ResponseInterface
    {
        $options['auth_basic'] = [$this->username, $this->password];

        return $this->httpClient->request($method, rtrim($this->baseUrl, '/') . $path, $options);
    }

    private function assertSuccessful(ResponseInterface $response, string $path): void
    {
        $statusCode = $response->getStatusCode();

        if ($statusCode < 400) {
            return;
        }

        throw new VPNException(sprintf(
            'wg-easy %s returned HTTP %d: %s',
            $path,
            $statusCode,
            $response->getContent(false),
        ));
    }
}
