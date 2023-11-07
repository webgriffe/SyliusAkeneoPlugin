<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\Model;

final class Family implements ResourceInterface
{
    public function __construct(
        public string $code,
        public array $attributes = [],
        public string $attributeAsLabel = '',
        public string $attributeAsImage = '',
        public array $attributeRequirements = [],
        public array $labels = [],
    ) {
    }

    public static function create(string $code, array $data = []): self
    {
        return new self(
            $code,
            $data['attributes'] ?? [],
            $data['attribute_as_label'] ?? '',
            $data['attribute_as_image'] ?? '',
            $data['attribute_requirements'] ?? [],
            $data['labels'] ?? [],
        );
    }

    public function __serialize(): array
    {
        return [
            'code' => $this->code,
            'attributes' => $this->attributes,
            'attribute_as_label' => $this->attributeAsLabel,
            'attribute_as_image' => $this->attributeAsImage,
            'attribute_requirements' => $this->attributeRequirements,
            'labels' => $this->labels,
        ];
    }

    public function getIdentifier(): string
    {
        return $this->code;
    }
}
