<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin;

use Sylius\Component\Core\Model\ProductInterface;

final class PriorityValueHandlerResolver implements ValueHandlerResolverInterface
{
    private $valueHandlers = [];

    public function add(ValueHandlerInterface $valueHandler, int $priority = 0): void
    {
        $this->valueHandlers[] = ['handler' => $valueHandler, 'priority' => $priority];
        usort(
            $this->valueHandlers,
            function (array $a, array $b) {
                return $a['priority'] > $b['priority'];
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(ProductInterface $product, string $attribute, array $value): ?ValueHandlerInterface
    {
        /** @var ValueHandlerInterface[] $valueHandlers */
        $valueHandlers = array_column($this->valueHandlers, 'handler');
        foreach ($valueHandlers as $valueHandler) {
            if ($valueHandler->supports($product, $attribute, $value)) {
                return $valueHandler;
            }
        }

        return null;
    }
}
