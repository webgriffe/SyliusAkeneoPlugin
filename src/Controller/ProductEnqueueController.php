<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Controller;

use Sylius\Component\Product\Model\ProductInterface;
use Sylius\Component\Product\Repository\ProductRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Webgriffe\SyliusAkeneoPlugin\Entity\QueueItem;
use Webgriffe\SyliusAkeneoPlugin\Repository\QueueItemRepositoryInterface;

final class ProductEnqueueController extends AbstractController
{
    /** @var QueueItemRepositoryInterface */
    private $queueItemRepository;

    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    /** @var ProductRepositoryInterface */
    private $productRepository;

    /**
     * ProductEnqueueController constructor.
     */
    public function __construct(QueueItemRepositoryInterface $queueItemRepository, ProductRepositoryInterface $productRepository, UrlGeneratorInterface $urlGenerator)
    {
        $this->queueItemRepository = $queueItemRepository;
        $this->urlGenerator = $urlGenerator;
        $this->productRepository = $productRepository;
    }

    public function enqueue(int $productId): Response
    {
        /** @var ProductInterface|null $product */
        $product = $this->productRepository->find($productId);
        if ($product === null) {
            $this->addFlash(
                'warning',
                'Product not exist!'
            );

            return new RedirectResponse($this->urlGenerator->generate('sylius_admin_product_index'));
        }

        /** @var array $productEnqueued */
        $productEnqueued = $this->queueItemRepository->findBy([
            'akeneoIdentifier' => $product->getCode(),
            'akeneoEntity' => 'Product',
            'importedAt' => null,
        ]);
        if (count($productEnqueued) > 0) {
            $this->addFlash(
                'warning',
                'Product already enqueued!'
            );

            return new RedirectResponse($this->urlGenerator->generate('sylius_admin_product_index'));
        }

        if($product->getCode() === null) {
            $this->addFlash(
                'warning',
                'Product does not have code!'
            );

            return new RedirectResponse($this->urlGenerator->generate('sylius_admin_product_index'));
        }

        $queueItem = new QueueItem();
        $queueItem->setAkeneoEntity('Product');
        $queueItem->setAkeneoIdentifier($product->getCode());
        $queueItem->setCreatedAt(new \DateTime());
        $this->queueItemRepository->add($queueItem);

        $this->addFlash(
            'success',
            'Product enqueued successfully!'
        );

        return new RedirectResponse($this->urlGenerator->generate('sylius_admin_product_index'));
    }
}
