<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Controller;

use Symfony\Component\HttpFoundation\Response;

interface ProductEnqueueControllerInterface
{
    public function enqueue(int $productId): Response;
}
