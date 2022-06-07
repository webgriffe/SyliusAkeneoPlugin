<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Controller;

use Sylius\Component\Product\Model\ProductInterface;
use Sylius\Component\Product\Repository\ProductRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webgriffe\SyliusAkeneoPlugin\Entity\QueueItem;
use Webgriffe\SyliusAkeneoPlugin\Repository\QueueItemRepositoryInterface;
use Webmozart\Assert\Assert;

final class ProductEnqueueController extends AbstractController
{
    public function __construct(
        private QueueItemRepositoryInterface $queueItemRepository,
        private ProductRepositoryInterface $productRepository,
        private UrlGeneratorInterface $urlGenerator,
        private TranslatorInterface $translator
    ) {
    }

    public function enqueueAction(int $productId): Response
    {
        /** @var ProductInterface|null $product */
        $product = $this->productRepository->find($productId);
        if ($product === null) {
            throw new NotFoundHttpException('Product not found');
        }

        $alreadyEnqueued = [];
        $enqueued = [];
        foreach ($product->getVariants() as $productVariant) {
            $productVariantCode = $productVariant->getCode();
            Assert::notNull($productVariantCode);

            $productEnqueued = $this->queueItemRepository->findBy([
                'akeneoIdentifier' => $productVariantCode,
                'akeneoEntity' => 'Product',
                'importedAt' => null,
            ]);
            if (count($productEnqueued) > 0) {
                $alreadyEnqueued[] = $productVariantCode;

                continue;
            }

            $queueItem = new QueueItem();
            $queueItem->setAkeneoEntity('Product');
            $queueItem->setAkeneoIdentifier($productVariantCode);
            $queueItem->setCreatedAt(new \DateTime());
            $this->queueItemRepository->add($queueItem);

            $enqueued[] = $productVariantCode;
        }

        foreach ($alreadyEnqueued as $code) {
            $this->addFlash(
                'error',
                $this->translator->trans('webgriffe_sylius_akeneo.ui.product_already_enqueued', ['code' => $code]) // @phpstan-ignore-line
            );
        }
        foreach ($enqueued as $code) {
            $this->addFlash(
                'success',
                $this->translator->trans('webgriffe_sylius_akeneo.ui.enqueued_success', ['code' => $code])
            );
        }

        return $this->redirectToRoute('sylius_admin_product_index');
    }
}
