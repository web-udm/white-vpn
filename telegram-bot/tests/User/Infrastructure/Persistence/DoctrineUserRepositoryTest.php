<?php

declare(strict_types=1);

namespace App\Tests\User\Infrastructure\Persistence;

use App\User\Domain\Entity\User;
use App\User\Domain\ValueObject\TelegramId;
use App\User\Infrastructure\Persistence\DoctrineUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DoctrineUserRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private DoctrineUserRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->repository = new DoctrineUserRepository($this->entityManager);
    }

    public function testSaveAndFindByTelegramId(): void
    {
        // Arrange
        $user = new User(new TelegramId(123456789));

        // Act
        $this->repository->save($user);
        $this->entityManager->clear();
        $found = $this->repository->findByTelegramId(new TelegramId(123456789));

        // Assert
        $this->assertNotNull($found);
        $this->assertSame(123456789, $found->getTelegramId()->value);
    }

    public function testFindByTelegramIdReturnsNullWhenNotFound(): void
    {
        // Act
        $result = $this->repository->findByTelegramId(new TelegramId(999999999));

        // Assert
        $this->assertNull($result);
    }
}