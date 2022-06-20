<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Message;

final class ItemImport
{
    public function __construct(
        private string $akeneoEntity,
        private string $akeneoIdentifier,
    ) {
    }

    public function getAkeneoEntity(): string
    {
        return $this->akeneoEntity;
    }

    public function getAkeneoIdentifier(): string
    {
        return $this->akeneoIdentifier;
    }
}
