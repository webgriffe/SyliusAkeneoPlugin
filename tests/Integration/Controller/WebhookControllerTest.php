<?php

declare(strict_types=1);

namespace Tests\Webgriffe\SyliusAkeneoPlugin\Integration\Controller;

use Fidry\AliceDataFixtures\Persistence\PurgeMode;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\InMemoryProductApi;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\InMemoryProductModelApi;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\Model\Product;
use Tests\Webgriffe\SyliusAkeneoPlugin\InMemory\Client\Api\Model\ProductModel;
use Webgriffe\SyliusAkeneoPlugin\Controller\WebhookController;
use Webgriffe\SyliusAkeneoPlugin\Respository\ItemImportResultRepositoryInterface;

final class WebhookControllerTest extends KernelTestCase
{
    private WebhookController $webhookController;

    private ItemImportResultRepositoryInterface $itemImportResultRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->webhookController = self::getContainer()->get('webgriffe_sylius_akeneo.controller.webhook');
        $this->itemImportResultRepository = self::getContainer()->get('webgriffe_sylius_akeneo.repository.item_import_result');

        $fixtureLoader = self::getContainer()->get('fidry_alice_data_fixtures.loader.doctrine');
        $fixtureLoader->load([], [], [], PurgeMode::createDeleteMode());

        InMemoryProductApi::addResource(Product::create('PRODUCT'));

        InMemoryProductModelApi::addResource(ProductModel::create('PRODUCT_MODEL', [
            'family' => 'family',
            'family_variant' => 'family_variant',
        ]));
    }

    /** @test */
    public function it_imports_created_products_on_akeneo(): void
    {
        $body = ['events' => [
            [
                'action' => 'product.created',
                'event_id' => '1',
                'data' => [
                    'resource' => [
                        'identifier' => 'PRODUCT',
                    ],
                ],
            ],
        ]];
        $request = new Request([], [], [], [], [], [], json_encode($body, \JSON_THROW_ON_ERROR));

        $timestamp = (string) time();
        $signature = hash_hmac('sha256', $timestamp . '.' . json_encode($body, \JSON_THROW_ON_ERROR), '');

        $request->headers->set('x-akeneo-request-timestamp', $timestamp);
        $request->headers->set('x-akeneo-request-signature', $signature);
        $this->webhookController->postAction($request);

        $itemImportResults = $this->itemImportResultRepository->findAll();
        self::assertCount(2, $itemImportResults);
        self::assertEquals('Successfully imported item "Product" with identifier "PRODUCT" from Akeneo.', $itemImportResults[0]->getMessage());
        self::assertEquals('Successfully imported item "ProductAssociations" with identifier "PRODUCT" from Akeneo.', $itemImportResults[1]->getMessage());
    }

    /** @test */
    public function it_imports_created_product_models_on_akeneo(): void
    {
        $body = ['events' => [
            [
                'action' => 'product_model.created',
                'event_id' => '1',
                'data' => [
                    'resource' => [
                        'code' => 'PRODUCT_MODEL',
                    ],
                ],
            ],
        ]];
        $request = new Request([], [], [], [], [], [], json_encode($body, \JSON_THROW_ON_ERROR));

        $timestamp = (string) time();
        $signature = hash_hmac('sha256', $timestamp . '.' . json_encode($body, \JSON_THROW_ON_ERROR), '');

        $request->headers->set('x-akeneo-request-timestamp', $timestamp);
        $request->headers->set('x-akeneo-request-signature', $signature);
        $this->webhookController->postAction($request);

        $itemImportResults = $this->itemImportResultRepository->findAll();
        self::assertCount(3, $itemImportResults);
        self::assertEquals('Successfully imported item "Product" with identifier "PRODUCT" from Akeneo.', $itemImportResults[0]->getMessage());
        self::assertEquals('Successfully imported item "ProductAssociations" with identifier "PRODUCT" from Akeneo.', $itemImportResults[1]->getMessage());
        self::assertEquals('Successfully imported item "ProductModel" with identifier "PRODUCT_MODEL" from Akeneo.', $itemImportResults[2]->getMessage());
    }

    /** @test */
    public function it_fails_if_secret_is_not_right(): void
    {
        $body = ['events' => [
            [
                'action' => 'product.created',
                'event_id' => '1',
                'data' => [
                    'resource' => [
                        'identifier' => 'PRODUCT',
                    ],
                ],
            ],
        ]];
        $request = new Request([], [], [], [], [], [], json_encode($body, \JSON_THROW_ON_ERROR));

        $timestamp = (string) time();
        $signature = hash_hmac('sha256', $timestamp . '.' . json_encode($body, \JSON_THROW_ON_ERROR), 'PIPPO');

        $request->headers->set('x-akeneo-request-timestamp', $timestamp);
        $request->headers->set('x-akeneo-request-signature', $signature);
        $this->webhookController->postAction($request);

        $itemImportResults = $this->itemImportResultRepository->findAll();
        self::assertCount(0, $itemImportResults);
    }

    /** @test */
    public function it_accepts_test_webhook_from_akeneo(): void
    {
        $request = new Request([], [], [], [], [], [], null);

        $timestamp = (string) time();
        $signature = hash_hmac('sha256', $timestamp . '.', '');

        $request->headers->set('x-akeneo-request-timestamp', $timestamp);
        $request->headers->set('x-akeneo-request-signature', $signature);
        $response = $this->webhookController->postAction($request);

        self::assertEquals(200, $response->getStatusCode());
    }
}
