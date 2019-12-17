<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Entity;

final class QueueItem implements QueueItemInterface
{
    /**
     * @var mixed
     */
    private $id;
    /**
     * @var string
     */
    private $akeneoEntity;
    /**
     * @var string
     */
    private $akeneoIdentifier;
    /**
     * @var \DateTimeInterface
     */
    private $createdAt;
    /**
     * @var \DateTimeInterface|null
     */
    private $importedAt;

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

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getImportedAt(): ?\DateTimeInterface
    {
        return $this->importedAt;
    }

    /**
     * @param string $akeneoIdentifier
     */
    public function setAkeneoIdentifier(string $akeneoIdentifier): void
    {
        $this->akeneoIdentifier = $akeneoIdentifier;
    }

    /**
     * @param string $akeneoEntity
     */
    public function setAkeneoEntity(string $akeneoEntity): void
    {
        $this->akeneoEntity = $akeneoEntity;
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
