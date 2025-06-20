<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Product;

use Sylius\Component\Core\Model\ProductVariantInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface as SymfonyValidator;
use Webgriffe\SyliusAkeneoPlugin\Product\Exception\ValidationException;

final class Validator implements ValidatorInterface
{
    /**
     * @param array<array-key, string> $productValidationGroups
     * @param array<array-key, string> $productVariantValidationGroups
     */
    public function __construct(
        private SymfonyValidator $validator,
        private array $productValidationGroups,
        private array $productVariantValidationGroups,
    ) {
    }

    #[\Override]
    public function validate(ProductVariantInterface $productVariant): void
    {
        /** @var ConstraintViolationList $errors */
        $errors = $this->validator->validate($productVariant, null, $this->productVariantValidationGroups);
        $errors->addAll($this->validator->validate($productVariant->getProduct(), null, $this->productValidationGroups));
        if (count($errors) > 0) {
            throw new ValidationException((string) $errors);
        }
    }
}
