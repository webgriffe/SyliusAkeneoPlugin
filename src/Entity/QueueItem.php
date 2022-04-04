<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Entity;

use DateTimeInterface;

/** @final */
/** @internal */
class QueueItem implements QueueItemInterface
{
    private ?mixed $id = null;

    private ?string $akeneoEntity = null;

    private ?string $akeneoIdentifier = null;

    private ?string $errorMessage = null;

    private ?DateTimeInterface $createdAt = null;

    private ?DateTimeInterface $importedAt = null;

    /** @return mixed */
    public function getId()
    {
        return $this->id;
    }

    public function getAkeneoEntity(): string
    {
        return $this->akeneoEntity;
    }

    public function getAkeneoIdentifier(): string
    {
        return $this->akeneoIdentifier;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getImportedAt(): ?DateTimeInterface
    {
        return $this->importedAt;
    }

    public function setAkeneoIdentifier(string $akeneoIdentifier): void
    {
        $this->akeneoIdentifier = $akeneoIdentifier;
    }

    public function setAkeneoEntity(string $akeneoEntity): void
    {
        $this->akeneoEntity = $akeneoEntity;
    }

    public function setErrorMessage(?string $errorMessage): void
    {
        $this->errorMessage = $errorMessage;
    }

    public function setCreatedAt(DateTimeInterface $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function setImportedAt(?DateTimeInterface $importedAt): void
    {
        $this->importedAt = $importedAt;
    }
}
