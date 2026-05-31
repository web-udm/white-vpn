<?php

declare(strict_types=1);

namespace App\VPN\Infrastructure\Xui;

use App\VPN\Domain\VPNProviderInterface;
use App\VPN\Port\VPNException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class XuiVPNProvider implements VPNProviderInterface
{
    private ?string $sessionCookie = null;
    private ?string $csrfToken = null;

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

    /** @param int[] $inboundIds */
    public function createClient(string $subId, array $inboundIds, int $limitIp = 3, int $expiryTimestamp = 0): void
    {
        $response = $this->request('POST', '/panel/api/clients/add', [
            'json' => [
                'client' => [
                    'email'      => $subId,
                    'subId'      => $subId,
                    'limitIp'   => $limitIp,
                    'totalGB'   => 0,
                    'expiryTime' => $expiryTimestamp,
                    'tgId'      => 0,
                    'enable'    => true,
                    'reset'     => 0,
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
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $options = []): array
    {
        try {
            $this->ensureAuthenticated();
            $response = $this->doRequest($method, $path, $options);

            if ($response->getStatusCode() === 403) {
                $this->invalidateSession();
                $this->ensureAuthenticated();
                $response = $this->doRequest($method, $path, $options);
            }

            return $response->toArray(false);
        } catch (VPNException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new VPNException('3x-ui request failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @param array<string, mixed> $options
     */
    private function doRequest(string $method, string $path, array $options): ResponseInterface
    {
        $options['headers'] = array_merge($options['headers'] ?? [], [
            'Cookie'       => $this->sessionCookie,
            'X-CSRF-Token' => $this->csrfToken,
        ]);

        return $this->httpClient->request($method, rtrim($this->baseUrl, '/') . $path, $options);
    }

    private function ensureAuthenticated(): void
    {
        if ($this->sessionCookie !== null && $this->csrfToken !== null) {
            return;
        }
        $this->login();
    }

    private function invalidateSession(): void
    {
        $this->sessionCookie = null;
        $this->csrfToken = null;
    }

    private function login(): void
    {
        try {
            $base = rtrim($this->baseUrl, '/');

            $pageResponse = $this->httpClient->request('GET', $base . '/');
            $html = $pageResponse->getContent(false);

            if (!preg_match('/<meta name="csrf-token" content="([^"]+)"/', $html, $matches)) {
                throw new VPNException('Failed to extract CSRF token from 3x-ui panel page');
            }
            $csrfToken = $matches[1];
            $initialCookie = $this->parseCookieHeader($pageResponse->getHeaders(false)['set-cookie'] ?? []);

            $loginResponse = $this->httpClient->request('POST', $base . '/login', [
                'json' => ['username' => $this->username, 'password' => $this->password],
                'headers' => [
                    'X-CSRF-Token' => $csrfToken,
                    'Cookie'       => $initialCookie,
                ],
            ]);

            $body = $loginResponse->toArray(false);
            if (!($body['success'] ?? false)) {
                throw new VPNException('3x-ui login failed: ' . ($body['msg'] ?? 'unknown error'));
            }

            $sessionCookie = $this->parseCookieHeader($loginResponse->getHeaders(false)['set-cookie'] ?? [])
                ?? $initialCookie;

            if ($sessionCookie === null) {
                throw new VPNException('No session cookie received from 3x-ui after login');
            }

            $this->sessionCookie = $sessionCookie;
            $this->csrfToken = $csrfToken;
        } catch (VPNException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new VPNException('3x-ui login failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /** @param string[] $setCookieHeaders */
    private function parseCookieHeader(array $setCookieHeaders): ?string
    {
        foreach ($setCookieHeaders as $header) {
            if (preg_match('/(3x-ui=[^;]+)/', $header, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }
}
