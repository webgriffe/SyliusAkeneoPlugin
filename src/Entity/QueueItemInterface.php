<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Entity;

use Sylius\Component\Resource\Model\ResourceInterface;

interface QueueItemInterface extends ResourceInterface
{
    public const AKENEO_ENTITY_PRODUCT_MODEL = 'ProductModel';

    public const AKENEO_ENTITY_PRODUCT = 'Product';

    public function getAkeneoEntity(): string;

    public function getAkeneoIdentifier(): string;

    public function getErrorMessage(): ?string;

    public function getCreatedAt(): \DateTimeInterface;

    public function getImportedAt(): ?\DateTimeInterface;

    public function setAkeneoIdentifier(string $identifier): void;

    public function setAkeneoEntity(string $entity): void;

    public function setErrorMessage(?string $errorMessage): void;

    public function setCreatedAt(\DateTimeInterface $createdAt): void;

    public function setImportedAt(?\DateTimeInterface $importedAt): void;
}
