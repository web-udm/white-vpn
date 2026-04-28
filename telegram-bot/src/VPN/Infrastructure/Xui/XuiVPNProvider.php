<?php

declare(strict_types=1);

namespace App\VPN\Infrastructure\Xui;

use App\VPN\Domain\VPNProviderInterface;
use App\VPN\Port\VPNException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class XuiVPNProvider implements VPNProviderInterface
{
    private ?string $sessionCookie = null;

    /**
     * @param int[] $inboundIds
     */
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire('%xui.base_url%')] private readonly string $baseUrl,
        #[Autowire('%xui.subscription_url%')] private readonly string $subscriptionUrl,
        #[Autowire('%xui.username%')] private readonly string $username,
        #[Autowire('%xui.password%')] private readonly string $password,
        #[Autowire('%xui.inbound_ids%')] private readonly array $inboundIds,
    ) {
    }

    public function createClient(string $subId, int $inboundId, int $limitIp = 3, int $expiryTimestamp = 0): void
    {
        $uuid = $this->generateUuid();
        $email = $this->buildEmail($subId, $inboundId);

        $clientSettings = json_encode([
            'clients' => [[
                'id' => $uuid,
                'password' => $uuid, // Trojan inbounds use password instead of id
                'email' => $email,
                'limitIp' => $limitIp,
                'totalGB' => 0,
                'expiryTime' => $expiryTimestamp,
                'enable' => true,
                'flow' => '',
                'tgId' => '',
                'subId' => $subId,
                'comment' => '',
                'reset' => 0,
            ]],
        ], JSON_THROW_ON_ERROR);

        $response = $this->request('POST', '/panel/api/inbounds/addClient', [
            'json' => [
                'id' => $inboundId,
                'settings' => $clientSettings,
            ],
        ]);

        if (!($response['success'] ?? false)) {
            throw new VPNException('Failed to create client: ' . ($response['msg'] ?? 'unknown error'));
        }
    }

    /** @return int[] */
    public function getInboundIds(): array
    {
        return array_map(intval(...), $this->inboundIds);
    }

    public function getConnectionURL(string $subId): string
    {
        return rtrim($this->subscriptionUrl, '/') . '/sub/' . $subId;
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $options = []): array
    {
        $this->ensureAuthenticated();

        $response = $this->doRequest($method, $path, $options);

        if ($response->getStatusCode() === 401) {
            $this->sessionCookie = null;
            $this->ensureAuthenticated();
            $response = $this->doRequest($method, $path, $options);
        }

        try {
            return $response->toArray(false);
        } catch (\Throwable $e) {
            throw new VPNException('Failed to parse 3x-ui response: ' . $e->getMessage(), 0, $e);
        }
    }

    private function ensureAuthenticated(): void
    {
        if ($this->sessionCookie !== null) {
            return;
        }

        $response = $this->httpClient->request('POST', $this->baseUrl . '/login', [
            'json' => [
                'username' => $this->username,
                'password' => $this->password,
            ],
        ]);

        $headers = $response->getHeaders(false);
        $cookies = $headers['set-cookie'] ?? [];

        foreach ($cookies as $cookie) {
            if (str_starts_with($cookie, '3x-ui=') || str_starts_with($cookie, 'session=')) {
                $this->sessionCookie = explode(';', $cookie)[0];
                return;
            }
        }

        $data = $response->toArray(false);

        if (!($data['success'] ?? false)) {
            throw new VPNException('3x-ui login failed: ' . ($data['msg'] ?? 'unknown error'));
        }

        throw new VPNException('3x-ui login succeeded but no session cookie received');
    }

    /**
     * @param array<string, mixed> $options
     */
    private function doRequest(string $method, string $path, array $options = []): \Symfony\Contracts\HttpClient\ResponseInterface
    {
        $options['headers'] = array_merge($options['headers'] ?? [], [
            'Cookie' => $this->sessionCookie,
        ]);

        return $this->httpClient->request($method, $this->baseUrl . $path, $options);
    }

    private function buildEmail(string $subId, int $inboundId): string
    {
        return $subId . '_' . $inboundId;
    }

    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return $data
                |> bin2hex(...)
                |> (fn($x) => str_split($x, 4))
                |> (fn($x) => vsprintf('%s%s-%s-%s-%s-%s%s%s', $x));
    }
}
