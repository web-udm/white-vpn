<?php

declare(strict_types=1);

namespace App\Tests\VPN\Infrastructure\Xui;

use App\VPN\Port\VPNException;
use App\VPN\Infrastructure\Xui\XuiVPNProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class XuiVPNProviderTest extends TestCase
{
    private function createProvider(array $responses, array $inboundIds = [1]): XuiVPNProvider
    {
        $mockClient = new MockHttpClient($responses, 'https://panel.example.com');

        return new XuiVPNProvider(
            httpClient: $mockClient,
            baseUrl: 'https://panel.example.com',
            subscriptionUrl: 'https://sub.example.com',
            username: 'admin',
            password: 'secret',
            inboundIds: $inboundIds,
        );
    }

    private function loginResponse(): MockResponse
    {
        return new MockResponse(
            json_encode(['success' => true, 'msg' => 'ok']),
            ['http_code' => 200, 'response_headers' => ['set-cookie' => '3x-ui=abc123; Path=/']],
        );
    }

    public function testCreateClientSuccess(): void
    {
        // Arrange
        $provider = $this->createProvider([
            $this->loginResponse(),
            new MockResponse(json_encode(['success' => true, 'msg' => 'ok'])),
        ]);

        // Act & Assert (no exception = success)
        $provider->createClient('some-uuid', 1, 3);
        $this->addToAssertionCount(1);
    }

    public function testCreateClientThrowsOnFailure(): void
    {
        // Arrange
        $provider = $this->createProvider([
            $this->loginResponse(),
            new MockResponse(json_encode(['success' => false, 'msg' => 'client already exists'])),
        ]);

        // Assert
        $this->expectException(VPNException::class);
        $this->expectExceptionMessage('client already exists');

        // Act
        $provider->createClient('some-uuid', 1);
    }

    public function testGetInboundIds(): void
    {
        // Arrange
        $provider = $this->createProvider([], [1, 9]);

        // Act & Assert
        $this->assertSame([1, 9], $provider->getInboundIds());
    }

    public function testGetConnectionURLReturnsURL(): void
    {
        // Arrange
        $provider = $this->createProvider([]);

        // Act
        $url = $provider->getConnectionURL('some-uuid');

        // Assert
        $this->assertSame('https://sub.example.com/sub/some-uuid', $url);
    }

    public function testReauthenticatesOn401(): void
    {
        // Arrange
        $provider = $this->createProvider([
            $this->loginResponse(),
            new MockResponse('', ['http_code' => 401]),
            $this->loginResponse(),
            new MockResponse(json_encode(['success' => true, 'msg' => 'ok'])),
        ]);

        // Act & Assert (no exception = success after re-auth)
        $provider->createClient('some-uuid', 1);
        $this->addToAssertionCount(1);
    }

    public function testLoginFailureThrows(): void
    {
        // Arrange
        $provider = $this->createProvider([
            new MockResponse(
                json_encode(['success' => false, 'msg' => 'invalid credentials']),
                ['http_code' => 200],
            ),
        ]);

        // Assert
        $this->expectException(VPNException::class);
        $this->expectExceptionMessage('invalid credentials');

        // Act
        $provider->createClient('some-uuid', 1);
    }
}
