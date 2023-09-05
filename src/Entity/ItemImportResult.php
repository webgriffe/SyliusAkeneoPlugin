<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Entity;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

/**
 * @readonly
 *
 * @final
 *
 * @internal
 */
class ItemImportResult implements ItemImportResultInterface
{
    private ?int $id = null;

    private DateTimeInterface $createdAt;

    public function __construct(
        private string $akeneoEntity,
        private string $akeneoIdentifier,
        private bool $successful,
        private string $message,
    ) {
        $this->createdAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getAkeneoEntity(): string
    {
        return $this->akeneoEntity;
    }

    public function getAkeneoIdentifier(): string
    {
        return $this->akeneoIdentifier;
    }

    public function isSuccessful(): bool
    {
        return $this->successful;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
