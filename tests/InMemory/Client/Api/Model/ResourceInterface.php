<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\Model;

interface ResourceInterface
{
    public static function create(string $code, array $data = []): self;

    public function __serialize(): array;

    public function getIdentifier(): string;
}
