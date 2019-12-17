<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Entity;

final class QueueItem implements QueueItemInterface
{
    /**
     * @var string
     */
    private $identifier;

    /**
     * @var string
     */
    private $entity;
    /**
     * @var \DateTimeInterface
     */
    private $createdAt;
    /**
     * @var \DateTimeInterface|null
     */
    private $importedAt;


    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getEntity(): string
    {
        return $this->entity;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getImportedAt(): ?\DateTimeInterface
    {
        return $this->importedAt;
    }

    /**
     * @param string $identifier
     */
    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }

    /**
     * @param string $entity
     */
    public function setEntity(string $entity): void
    {
        $this->entity = $entity;
    }

    /**
     * @param \DateTimeInterface $createdAt
     */
    public function setCreatedAt(\DateTimeInterface $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @param \DateTimeInterface|null $importedAt
     */
    public function setImportedAt(?\DateTimeInterface $importedAt): void
    {
        $this->importedAt = $importedAt;
    }
}
