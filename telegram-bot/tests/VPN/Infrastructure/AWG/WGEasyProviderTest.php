<?php

declare(strict_types=1);

namespace App\Tests\VPN\Infrastructure\AWG;

use App\VPN\Infrastructure\AWG\WGEasyProvider;
use App\VPN\Port\VPNException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class WGEasyProviderTest extends TestCase
{
    /** @var array<int, array{method: string, url: string, options: array<string, mixed>}> */
    private array $requests = [];

    public function testCreatePeerReturnsClientId(): void
    {
        // Arrange
        $provider = $this->createProvider([
            new MockResponse(json_encode(['success' => true, 'clientId' => 42])),
        ]);

        // Act
        $peerId = $provider->createPeer('some-uuid');

        // Assert
        $this->assertSame('42', $peerId);
        $this->assertSame('POST', $this->requests[0]['method']);
        $this->assertSame('https://awg.example.com/api/client', $this->requests[0]['url']);
        $this->assertSame(
            ['name' => 'some-uuid', 'expiresAt' => null],
            json_decode((string) $this->requests[0]['options']['body'], true),
        );
    }

    public function testCreatePeerSendsBasicAuth(): void
    {
        // Arrange
        $provider = $this->createProvider([
            new MockResponse(json_encode(['success' => true, 'clientId' => 1])),
        ]);

        // Act
        $provider->createPeer('some-uuid');

        // Assert
        $this->assertContains(
            'Authorization: Basic ' . base64_encode('admin:password'),
            $this->requests[0]['options']['headers'],
        );
    }

    public function testCreatePeerThrowsWhenClientIdMissing(): void
    {
        // Arrange
        $provider = $this->createProvider([
            new MockResponse(json_encode(['success' => true])),
        ]);

        // Assert
        $this->expectException(VPNException::class);
        $this->expectExceptionMessage('no clientId in response');

        // Act
        $provider->createPeer('some-uuid');
    }

    public function testCreatePeerThrowsOnErrorStatus(): void
    {
        // Arrange
        $provider = $this->createProvider([
            new MockResponse(json_encode(['message' => 'Unauthorized']), ['http_code' => 401]),
        ]);

        // Assert
        $this->expectException(VPNException::class);
        $this->expectExceptionMessage('HTTP 401');

        // Act
        $provider->createPeer('some-uuid');
    }

    public function testGetPeerConfigReturnsRawBody(): void
    {
        // Arrange
        $config   = "[Interface]\nPrivateKey = key\nJc = 4\nS1 = 50\nH1 = 1234567891\n";
        $provider = $this->createProvider([new MockResponse($config)]);

        // Act
        $result = $provider->getPeerConfig('42');

        // Assert
        $this->assertSame($config, $result);
        $this->assertSame('GET', $this->requests[0]['method']);
        $this->assertSame('https://awg.example.com/api/client/42/configuration', $this->requests[0]['url']);
    }

    public function testListPeersMapsIdAndName(): void
    {
        // Arrange
        $provider = $this->createProvider([
            new MockResponse(json_encode([
                ['id' => 1, 'name' => 'first', 'enabled' => true],
                ['id' => 2, 'name' => 'second', 'enabled' => true],
            ])),
        ]);

        // Act
        $peers = $provider->listPeers();

        // Assert
        $this->assertSame([
            ['id' => '1', 'name' => 'first'],
            ['id' => '2', 'name' => 'second'],
        ], $peers);
    }

    public function testDeletePeerUsesDeleteMethod(): void
    {
        // Arrange
        $provider = $this->createProvider([
            new MockResponse(json_encode(['success' => true])),
        ]);

        // Act
        $provider->deletePeer('42');

        // Assert
        $this->assertSame('DELETE', $this->requests[0]['method']);
        $this->assertSame('https://awg.example.com/api/client/42', $this->requests[0]['url']);
    }

    public function testGenerateQrPngFromConfigReturnsPng(): void
    {
        // Arrange
        $provider = $this->createProvider([]);

        // Act
        $png = $provider->generateQrPngFromConfig("[Interface]\nPrivateKey = key\n");

        // Assert
        $this->assertStringStartsWith("\x89PNG", $png);
    }

    /** @param MockResponse[] $responses */
    private function createProvider(array $responses): WGEasyProvider
    {
        $mockClient = new MockHttpClient(
            function (string $method, string $url, array $options) use (&$responses): ResponseInterface {
                $this->requests[] = ['method' => $method, 'url' => $url, 'options' => $options];

                return array_shift($responses) ?? new MockResponse('');
            },
            'https://awg.example.com',
        );

        return new WGEasyProvider(
            httpClient: $mockClient,
            baseUrl: 'https://awg.example.com',
            username: 'admin',
            password: 'password',
        );
    }
}
