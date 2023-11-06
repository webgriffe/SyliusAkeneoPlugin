<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\Model;

use DateTimeImmutable;
use DateTimeInterface;

final class Product implements ResourceInterface
{
    public DateTimeInterface $created;
    public DateTimeInterface $updated;

    /**
     * @param string[] $categories
     * @param string[] $groups
     * @param array<string, array{'locale': ?string, 'scope': ?string, 'data': mixed}> $values
     * @param array<string, array{'products': string[], 'product_models': string[], 'groups': string[]}> $associations
     * @param array $quantifiedAssociations
     */
    private function __construct(
        public string $identifier,
        public bool $enabled = true,
        public ?string $family = null,
        public array $categories = [],
        public array $groups = [],
        public ?string $parent = null,
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
        return new self(
            $code,
            $data['enabled'] ?? true,
            $data['family'] ?? null,
            $data['categories'] ?? [],
            $data['groups'] ?? [],
            $data['parent'] ?? null,
            $data['values'] ?? [],
            $data['associations'] ?? [],
            $data['quantifiedAssociations'] ?? [],
        );
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function __serialize(): array
    {
        return [
            'identifier' => $this->identifier,
            'enabled' => $this->enabled,
            'family' => $this->family,
            'categories' => $this->categories,
            'groups' => $this->groups,
            'parent' => $this->parent,
            'values' => $this->values,
            'created' => $this->created->format('c'),
            'updated' => $this->updated->format('c'),
            'associations' => $this->associations,
            'quantified_associations' => $this->quantifiedAssociations,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->identifier = $data['identifier'];
        $this->enabled = $data['enabled'];
        $this->family = $data['family'];
        $this->categories = $data['categories'];
        $this->groups = $data['groups'];
        $this->parent = $data['parent'];
        $this->values = $data['values'];
        $this->created = $data['created'];
        $this->updated = $data['updated'];
        $this->associations = $data['associations'];
        $this->quantifiedAssociations = $data['quantified_associations'];
    }
}
