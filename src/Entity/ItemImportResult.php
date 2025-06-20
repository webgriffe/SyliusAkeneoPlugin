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

    #[\Override]
    public function getId(): ?int
    {
        return $this->id;
    }

    #[\Override]
    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    #[\Override]
    public function getAkeneoEntity(): string
    {
        return $this->akeneoEntity;
    }

    #[\Override]
    public function getAkeneoIdentifier(): string
    {
        return $this->akeneoIdentifier;
    }

    #[\Override]
    public function isSuccessful(): bool
    {
        return $this->successful;
    }

    #[\Override]
    public function getMessage(): string
    {
        return $this->message;
    }
}
