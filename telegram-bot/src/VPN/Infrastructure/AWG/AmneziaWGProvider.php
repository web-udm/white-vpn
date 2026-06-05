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

final class AmneziaWGProvider implements AWGProviderInterface
{
    private ?string $sessionCookie = null;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire('%awg.base_url%')] private readonly string $baseUrl,
        #[Autowire('%awg.password%')] private readonly string $password,
    ) {
    }

    public function createPeer(string $name): string
    {
        $this->request('POST', '/api/wireguard/client', [
            'json' => ['name' => $name],
        ]);

        foreach ($this->listPeers() as $peer) {
            if ($peer['name'] === $name) {
                return $peer['id'];
            }
        }

        throw new VPNException('AWG createPeer: peer created but not found in list by name: ' . $name);
    }

    public function getPeerConfig(string $peerId): string
    {
        return $this->requestRaw('GET', '/api/wireguard/client/' . rawurlencode($peerId) . '/configuration');
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

    public function renamePeer(string $peerId, string $name): void
    {
        $this->request('PUT', '/api/wireguard/client/' . rawurlencode($peerId) . '/name', [
            'json' => ['name' => $name],
        ]);
    }

    public function deletePeer(string $peerId): void
    {
        $this->request('DELETE', '/api/wireguard/client/' . rawurlencode($peerId));
    }

    /** @return array<array{id: string, name: string}> */
    public function listPeers(): array
    {
        $response = $this->request('GET', '/api/wireguard/client');

        if (!is_array($response)) {
            throw new VPNException('AWG listPeers: unexpected response format');
        }

        return array_map(static function (mixed $peer): array {
            return [
                'id'   => (string) ($peer['id'] ?? ''),
                'name' => (string) ($peer['name'] ?? ''),
            ];
        }, $response);
    }

    /** @param array<string, mixed> $options */
    private function request(string $method, string $path, array $options = []): mixed
    {
        try {
            $this->ensureAuthenticated();
            $response = $this->doRequest($method, $path, $options);

            if ($response->getStatusCode() === 401 || $response->getStatusCode() === 403) {
                $this->invalidateSession();
                $this->ensureAuthenticated();
                $response = $this->doRequest($method, $path, $options);
            }

            return $response->toArray(false);
        } catch (VPNException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new VPNException('AWG request failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /** @param array<string, mixed> $options */
    private function requestRaw(string $method, string $path, array $options = []): string
    {
        try {
            $this->ensureAuthenticated();
            $response = $this->doRequest($method, $path, $options);

            if ($response->getStatusCode() === 401 || $response->getStatusCode() === 403) {
                $this->invalidateSession();
                $this->ensureAuthenticated();
                $response = $this->doRequest($method, $path, $options);
            }

            return $response->getContent(false);
        } catch (VPNException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new VPNException('AWG request failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /** @param array<string, mixed> $options */
    private function doRequest(string $method, string $path, array $options = []): ResponseInterface
    {
        $options['headers'] = array_merge($options['headers'] ?? [], [
            'Cookie' => $this->sessionCookie,
        ]);

        return $this->httpClient->request($method, rtrim($this->baseUrl, '/') . $path, $options);
    }

    private function ensureAuthenticated(): void
    {
        if ($this->sessionCookie !== null) {
            return;
        }

        $this->login();
    }

    private function invalidateSession(): void
    {
        $this->sessionCookie = null;
    }

    private function login(): void
    {
        try {
            $response = $this->httpClient->request('POST', rtrim($this->baseUrl, '/') . '/api/session', [
                'json' => ['password' => $this->password],
            ]);

            $body = $response->toArray(false);

            if (!($body['success'] ?? false)) {
                throw new VPNException('AWG login failed: ' . json_encode($body));
            }

            $cookie = $this->parseCookieHeader($response->getHeaders(false)['set-cookie'] ?? []);

            if ($cookie === null) {
                throw new VPNException('AWG login: no session cookie in response');
            }

            $this->sessionCookie = $cookie;
        } catch (VPNException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new VPNException('AWG login failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /** @param string[] $setCookieHeaders */
    private function parseCookieHeader(array $setCookieHeaders): ?string
    {
        foreach ($setCookieHeaders as $header) {
            if (preg_match('/^([^=]+=\S+)/', $header, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }
}
