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

    private ?TranslatorInterface $translator;

    /**
     * ProductEnqueueController constructor.
     */
    public function __construct(
        QueueItemRepositoryInterface $queueItemRepository,
        ProductRepositoryInterface $productRepository,
        UrlGeneratorInterface $urlGenerator,
        TranslatorInterface $translator = null
    ) {
        $this->queueItemRepository = $queueItemRepository;
        $this->urlGenerator = $urlGenerator;
        $this->productRepository = $productRepository;
        if ($translator === null) {
            trigger_deprecation(
                'webgriffe/sylius-akeneo-plugin',
                '1.12',
                'Not passing a translator to "%s" is deprecated and will be removed in %s.',
                __CLASS__,
                '2.0'
            );
        }
        $this->translator = $translator;
    }

    public function enqueueAction(int $productId): Response
    {
        if ($this->translator === null) {
            /**
             * @psalm-suppress DeprecatedMethod
             */
            $translator = $this->get('translator');
            Assert::isInstanceOf($translator, TranslatorInterface::class);
            $this->translator = $translator;
        }
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
