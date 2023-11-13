<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\Model;

final class AttributeOption
{
    public function __construct(
        public string $code,
        public string $attribute,
        public int $sortOrder,
        public array $labels,
    ) {
    }

    public static function create(string $attribute, string $code, int $sortOrder = 0, array $labels = []): self
    {
        return new self(
            $code,
            $attribute,
            $sortOrder,
            $labels,
        );
    }

    public function __serialize(): array
    {
        return [
            'code' => $this->code,
            'attribute' => $this->attribute,
            'sort_order' => $this->sortOrder,
            'labels' => $this->labels,
        ];
    }

    public function getIdentifier(): string
    {
        return $this->code;
    }
}
