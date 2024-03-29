<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Controller;

use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Product\Model\ProductInterface;
use Sylius\Component\Product\Repository\ProductRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webgriffe\SyliusAkeneoPlugin\Message\ItemImport;
use Webgriffe\SyliusAkeneoPlugin\Product\Importer as ProductImporter;
use Webgriffe\SyliusAkeneoPlugin\ProductAssociations\Importer as ProductAssociationsImporter;
use Webgriffe\SyliusAkeneoPlugin\ProductModel\Importer as ProductModelImporter;
use Webmozart\Assert\Assert;

final class ProductImportController extends AbstractController
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private MessageBusInterface $messageBus,
        private TranslatorInterface $translator,
    ) {
    }

    public function importAction(int $productId): Response
    {
        /** @var ProductInterface|null $product */
        $product = $this->productRepository->find($productId);
        if ($product === null) {
            throw new NotFoundHttpException('Product not found');
        }
        $productCode = $product->getCode();
        Assert::string($productCode);
        $this->messageBus->dispatch(new ItemImport(
            ProductModelImporter::AKENEO_ENTITY,
            $productCode,
        ));
        $this->addFlash(
            'success',
            $this->translator->trans('webgriffe_sylius_akeneo.ui.enqueued_success', ['{code}' => $productCode]),
        );
        if ($product->isSimple()) {
            $productVariant = $product->getVariants()->first();
            Assert::isInstanceOf($productVariant, ProductVariantInterface::class);
            $productVariantCode = $productVariant->getCode();
            Assert::string($productVariantCode);
            $this->messageBus->dispatch(new ItemImport(
                ProductImporter::AKENEO_ENTITY,
                $productVariantCode,
            ));
            $this->messageBus->dispatch(new ItemImport(
                ProductAssociationsImporter::AKENEO_ENTITY,
                $productVariantCode,
            ));
            $this->addFlash(
                'success',
                $this->translator->trans('webgriffe_sylius_akeneo.ui.enqueued_success', ['{code}' => $productVariantCode]),
            );
        }

        return $this->redirectToRoute('webgriffe_sylius_akeneo_admin_item_import_result_index');
    }
}
