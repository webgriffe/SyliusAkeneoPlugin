<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\ProductModel;

final class ValueHandlersRegistry implements ValueHandlersRegistryInterface
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
     * @return ValueHandlerInterface[]
     */
    public function all(): array
    {
        return array_column($this->valueHandlers, 'handler');
    }
}
