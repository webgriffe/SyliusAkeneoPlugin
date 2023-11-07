<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\Model;

use DateTimeInterface;

final class Attribute implements ResourceInterface
{
    public function __construct(
        public string $code,
        public string $type = AttributeType::TEXT,
        public ?string $group = null,
        public bool $unique = false,
        public bool $useableAsGridFilter = true,
        public array $allowedExtension = [],
        public ?string $metricFamily = null,
        public ?string $defaultMetricUnit = null,
        public ?string $referenceDataName = null,
        public array $availableLocales = [],
        public ?int $maxCharacters = null,
        public ?string $validationRule = null,
        public ?string $validationRegexp = null,
        public ?bool $wysiwygEnabled = null,
        public ?int $numberMin = null,
        public ?int $numberMax = null,
        public ?bool $decimalsAllowed = null,
        public ?bool $negativeAllowed = null,
        public ?DateTimeInterface $dateMin = null,
        public ?DateTimeInterface $dateMax = null,
        public ?int $maxFileSize = null,
        public ?int $minimumInputLength = null,
        public int $sortOrder = 0,
        public bool $localizable = false,
        public bool $scopable = false,
        public array $labels = [],
        public array $guidelines = [],
        public ?bool $autoOptionSorting = null,
        public mixed $defaultValue = null,
        public array $groupLabels = [],
    ) {
    }

    public static function create(string $code, array $data = []): self
    {
        return new self(
            $code,
            $data['type'] ?? AttributeType::TEXT,
            $data['group'] ?? null,
            $data['unique'] ?? false,
            $data['useable_as_grid_filter'] ?? true,
            $data['allowed_extensions'] ?? [],
            $data['metric_family'] ?? null,
            $data['default_metric_unit'] ?? null,
            $data['reference_data_name'] ?? null,
            $data['available_locales'] ?? [],
            $data['max_characters'] ?? null,
            $data['validation_rule'] ?? null,
            $data['validation_regexp'] ?? null,
            $data['wysiwyg_enabled'] ?? null,
            $data['number_min'] ?? null,
            $data['number_max'] ?? null,
            $data['decimals_allowed'] ?? null,
            $data['negative_allowed'] ?? null,
            $data['date_min'] ?? null,
            $data['date_max'] ?? null,
            $data['max_file_size'] ?? null,
            $data['minimum_input_length'] ?? null,
            $data['sort_order'] ?? 0,
            $data['localizable'] ?? false,
            $data['scopable'] ?? false,
            $data['labels'] ?? [],
            $data['guidelines'] ?? [],
            $data['auto_option_sorting'] ?? null,
            $data['default_value'] ?? null,
            $data['group_labels'] ?? [],
        );
    }

    public function __serialize(): array
    {
        return [
            'code' => $this->code,
            'type' => $this->type,
            'group' => $this->group,
            'unique' => $this->unique,
            'useable_as_grid_filter' => $this->useableAsGridFilter,
            'allowed_extensions' => $this->allowedExtension,
            'metric_family' => $this->metricFamily,
            'default_metric_unit' => $this->defaultMetricUnit,
            'reference_data_name' => $this->referenceDataName,
            'available_locales' => $this->availableLocales,
            'max_characters' => $this->maxCharacters,
            'validation_rule' => $this->validationRule,
            'validation_regexp' => $this->validationRegexp,
            'wysiwyg_enabled' => $this->wysiwygEnabled,
            'number_min' => $this->numberMin,
            'number_max' => $this->numberMax,
            'decimals_allowed' => $this->decimalsAllowed,
            'negative_allowed' => $this->negativeAllowed,
            'date_min' => $this->dateMin,
            'date_max' => $this->dateMax,
            'max_file_size' => $this->maxFileSize,
            'minimum_input_length' => $this->minimumInputLength,
            'sort_order' => $this->sortOrder,
            'localizable' => $this->localizable,
            'scopable' => $this->scopable,
            'labels' => $this->labels,
            'guidelines' => $this->guidelines,
            'auto_option_sorting' => $this->autoOptionSorting,
            'default_value' => $this->defaultValue,
            'group_labels' => $this->groupLabels,
        ];
    }

    public function getIdentifier(): string
    {
        return $this->code;
    }
}
