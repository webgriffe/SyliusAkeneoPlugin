<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Controller;

use Sylius\Component\Product\Model\ProductInterface;
use Sylius\Component\Product\Repository\ProductRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webgriffe\SyliusAkeneoPlugin\Message\ItemImport;
use Webgriffe\SyliusAkeneoPlugin\ProductModel\Importer;
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
            Importer::AKENEO_ENTITY,
            $productCode,
        ));

        $this->addFlash(
            'success',
            $this->translator->trans('webgriffe_sylius_akeneo.ui.enqueued_success', ['{code}' => $productCode]),
        );

        return $this->redirectToRoute('webgriffe_sylius_akeneo_admin_item_import_result_index');
    }
}
