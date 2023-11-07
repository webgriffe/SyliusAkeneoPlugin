<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\Model;

final class FamilyVariant implements ResourceInterface
{
    public function __construct(
        public string $code,
        public array $labels = [],
        public array $variantAttributeSets = [],
    ) {
    }

    public static function create(string $code, array $data = []): ResourceInterface
    {
        return new self(
            $code,
            $data['labels'] ?? [],
            $data['variant_attribute_sets'] ?? [],
        );
    }

    public function __serialize(): array
    {
        return [
            'code' => $this->code,
            'labels' => $this->labels,
            'variant_attribute_sets' => $this->variantAttributeSets,
        ];
    }

    public function getIdentifier(): string
    {
        return $this->code;
    }
}
