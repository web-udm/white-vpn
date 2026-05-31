<?php

declare(strict_types=1);

namespace App\VPN\Infrastructure\Xui;

use App\VPN\Domain\VPNProviderInterface;
use App\VPN\Port\VPNException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class XuiVPNProvider implements VPNProviderInterface
{
    /**
     * @param int[] $inboundIds
     */
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire('%xui.base_url%')] private readonly string $baseUrl,
        #[Autowire('%xui.subscription_url%')] private readonly string $subscriptionUrl,
        #[Autowire('%xui.api_token%')] private readonly string $apiToken,
        #[Autowire('%xui.inbound_ids%')] private readonly array $inboundIds,
    ) {
    }

    /** @param int[] $inboundIds */
    public function createClient(string $subId, array $inboundIds, int $limitIp = 3, int $expiryTimestamp = 0): void
    {
        $response = $this->request('POST', '/panel/api/clients/add', [
            'json' => [
                'client' => [
                    'email' => $subId,
                    'subId' => $subId,
                    'limitIp' => $limitIp,
                    'totalGB' => 0,
                    'expiryTime' => $expiryTimestamp,
                    'tgId' => 0,
                    'enable' => true,
                    'reset' => 0,
                ],
                'inboundIds' => $inboundIds,
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
     * For diagnostics only — returns raw status, headers, body.
     * @param array<string, mixed> $options
     * @return array{status: int, headers: array<string, mixed>, body: string}
     */
    public function rawRequest(string $method, string $path, array $options = []): array
    {
        $options['headers'] = array_merge($options['headers'] ?? [], [
            'Authorization' => 'Bearer ' . $this->apiToken,
        ]);

        $response = $this->httpClient->request($method, rtrim($this->baseUrl, '/') . $path, $options);

        return [
            'status'  => $response->getStatusCode(),
            'headers' => $response->getHeaders(false),
            'body'    => $response->getContent(false),
        ];
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $options = []): array
    {
        $options['headers'] = array_merge($options['headers'] ?? [], [
            'Authorization' => 'Bearer ' . $this->apiToken,
        ]);

        $response = $this->httpClient->request($method, rtrim($this->baseUrl, '/') . $path, $options);

        try {
            return $response->toArray(false);
        } catch (\Throwable $e) {
            throw new VPNException('Failed to parse 3x-ui response: ' . $e->getMessage(), 0, $e);
        }
    }
}
