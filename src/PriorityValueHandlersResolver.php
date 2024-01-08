<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin;

final class PriorityValueHandlersResolver implements ValueHandlersResolverInterface
{
    private array $valueHandlers = [];

    public function add(ValueHandlerInterface $valueHandler, int $priority = 0): void
    {
        $this->valueHandlers[] = ['handler' => $valueHandler, 'priority' => $priority];
        usort(
            $this->valueHandlers,
            static fn (array $a, array $b): int => $b['priority'] <=> $a['priority'],
        );
    }

    /**
     * @inheritdoc
     */
    public function resolve($subject, string $attribute, array $value): array
    {
        $supportedHandlers = [];
        /** @var ValueHandlerInterface[] $valueHandlers */
        $valueHandlers = array_column($this->valueHandlers, 'handler');
        foreach ($valueHandlers as $valueHandler) {
            if ($valueHandler->supports($subject, $attribute, $value)) {
                $supportedHandlers[] = $valueHandler;
            }
        }

        return $supportedHandlers;
    }
}
