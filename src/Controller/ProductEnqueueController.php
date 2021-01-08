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

    public function enqueueAction(int $productId): Response
    {
        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');
        /** @var ProductInterface|null $product */
        $product = $this->productRepository->find($productId);
        if ($product === null) {
            throw new NotFoundHttpException('Product not found');
        }

        /** @var array $productEnqueued */
        $productEnqueued = $this->queueItemRepository->findBy([
            'akeneoIdentifier' => $product->getCode(),
            'akeneoEntity' => 'Product',
            'importedAt' => null,
        ]);
        if (count($productEnqueued) > 0) {
            $this->addFlash(
                'error',
                $translator->trans('webgriffe_sylius_akeneo.ui.product_already_enqueued')
            );

            return $this->redirectToRoute('sylius_admin_product_index');
        }

        /** @var ?string $productCode */
        $productCode = $product->getCode();

        Assert::notNull($productCode);

        $queueItem = new QueueItem();
        $queueItem->setAkeneoEntity('Product');
        $queueItem->setAkeneoIdentifier($productCode);
        $queueItem->setCreatedAt(new \DateTime());
        $this->queueItemRepository->add($queueItem);

        $this->addFlash(
            'success',
            $translator->trans('webgriffe_sylius_akeneo.ui.enqueued_success')
        );

        return $this->redirectToRoute('sylius_admin_product_index');
    }
}
