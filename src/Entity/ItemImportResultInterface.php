<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Entity;

use DateTimeInterface;
use Sylius\Component\Resource\Model\ResourceInterface;

interface ItemImportResultInterface extends ResourceInterface
{
    public function getId(): ?int;

    public function getCreatedAt(): DateTimeInterface;

    public function getAkeneoEntity(): string;

    public function getAkeneoIdentifier(): string;

    public function isSuccessful(): bool;

    public function getMessage(): string;
}
