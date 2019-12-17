<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Entity;

interface QueueItemInterface
{
    public function getIdentifier(): string;

    public function getEntity(): string;

    public function getCreatedAt(): \DateTimeInterface;

    public function getImportedAt(): ?\DateTimeInterface;

    public function setIdentifier(string $identifier): void;

    public function setEntity(string $entity): void;

    public function setCreatedAt(\DateTimeInterface $createdAt): void;

    public function setImportedAt(?\DateTimeInterface $importedAt): void;
}
