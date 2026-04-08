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
    private function createProvider(array $responses): XuiVPNProvider
    {
        $mockClient = new MockHttpClient($responses, 'https://panel.example.com');

        return new XuiVPNProvider(
            httpClient: $mockClient,
            baseUrl: 'https://panel.example.com',
            subscriptionUrl: 'https://sub.example.com',
            username: 'admin',
            password: 'secret',
            inboundId: 1,
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
        $provider->createClient('some-uuid', 3);
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
        $provider->createClient('some-uuid');
    }

    public function testGetSubscriptionStatusReturnsStatus(): void
    {
        // Arrange
        $provider = $this->createProvider([
            $this->loginResponse(),
            new MockResponse(json_encode([
                'success' => true,
                'obj' => [
                    'email' => 'some-uuid_1',
                    'enable' => true,
                    'expiryTime' => 1750000000000,
                    'up' => 1024000,
                    'down' => 5120000,
                ],
            ])),
        ]);

        // Act
        $status = $provider->getSubscriptionStatus('some-uuid');

        // Assert
        $this->assertNotNull($status);
                $this->assertTrue($status->enabled);
        $this->assertSame(1750000000000, $status->expiryTime);
        $this->assertSame(1024000, $status->uploadBytes);
        $this->assertSame(5120000, $status->downloadBytes);
    }

    public function testGetSubscriptionStatusReturnsNullWhenNotFound(): void
    {
        // Arrange
        $provider = $this->createProvider([
            $this->loginResponse(),
            new MockResponse(json_encode(['success' => true, 'obj' => null])),
        ]);

        // Act
        $status = $provider->getSubscriptionStatus('nonexistent-uuid');

        // Assert
        $this->assertNull($status);
    }

    public function testGetSubscriptionURLReturnsURL(): void
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
            new MockResponse(json_encode([
                'success' => true,
                'obj' => [
                    'email' => 'some-uuid_1',
                    'enable' => true,
                    'expiryTime' => 0,
                    'up' => 0,
                    'down' => 0,
                ],
            ])),
        ]);

        // Act
        $status = $provider->getSubscriptionStatus('some-uuid');

        // Assert
        $this->assertNotNull($status);
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
        $provider->getSubscriptionStatus('some-uuid');
    }
}
