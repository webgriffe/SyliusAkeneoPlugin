<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\Model;

use DateTimeImmutable;
use DateTimeInterface;
use Webmozart\Assert\Assert;

final class ProductModel implements ResourceInterface
{
    public DateTimeInterface $created;
    public DateTimeInterface $updated;

    /**
     * @param string[] $categories
     * @param array<string, array{'locale': ?string, 'scope': ?string, 'data': mixed}> $values
     * @param array<string, array{'products': string[], 'product_models': string[], 'groups': string[]}> $associations
     * @param array $quantifiedAssociations
     */
    private function __construct(
        public string $code,
        public ?string $family = null,
        public ?string $familyVariant = null,
        public ?string $parent = null,
        public array $categories = [],
        public array $values = [],
        public array $associations = [],
        public array $quantifiedAssociations = [],
    ) {
        $now = new DateTimeImmutable();
        $this->created = $now;
        $this->updated = $now;
    }

    public static function create(string $code, array $data = []): self
    {
        Assert::keyExists($data, 'family');
        Assert::keyExists($data, 'family_variant');
        $family = $data['family'];
        $familyVariant = $data['family_variant'];

        return new self(
            $code,
            $family,
            $familyVariant,
            $data['parent'] ?? null,
        $data['categories'] ?? [],
            $data['values'] ?? [],
            $data['associations'] ?? [],
            $data['quantifiedAssociations'] ?? [],
        );
    }

    public function getIdentifier(): string
    {
        return $this->code;
    }

    public function __serialize(): array
    {
        return [
            'code' => $this->code,
            'family' => $this->family,
            'family_variant' => $this->familyVariant,
            'parent' => $this->parent,
            'categories' => $this->categories,
            'values' => $this->values,
            'created' => $this->created->format('c'),
            'updated' => $this->updated->format('c'),
            'associations' => $this->associations,
            'quantified_associations' => $this->quantifiedAssociations,
        ];
    }
}
