<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin;

trait ProductOptionHelperTrait
{
    protected function getSyliusProductOptionValueCode(string ...$pieces): string
    {
        $slugifiedPieces = array_map(static function (string $word): string {
            return str_replace(['.', ','], '', $word);
        }, $pieces);

        return implode('_', $slugifiedPieces);
    }
}
