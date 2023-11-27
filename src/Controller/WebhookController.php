<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Controller;

use const JSON_THROW_ON_ERROR;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Webgriffe\SyliusAkeneoPlugin\Message\ItemImport;
use Webgriffe\SyliusAkeneoPlugin\Product\Importer as ProductImporter;
use Webgriffe\SyliusAkeneoPlugin\ProductAssociations\Importer as ProductAssociationsImporter;

/**
 * @psalm-type AkeneoEventProduct = array{
 *     uuid: string,
 *     identifier: string,
 *     enabled: bool,
 *     family: string,
 *     categories: string[],
 *     groups: string[],
 *     parent: ?string,
 *     values: array<string, array>,
 *     created: string,
 *     updated: string,
 *     associations: array<string, array>,
 *     quantified_associations: array<string, array>,
 * }
 * @psalm-type AkeneoEventProductModel = array{
 *     code: string,
 *     family: string,
 *     family_variant: string,
 *     parent: ?string,
 *     categories: string[],
 *     values: array<string, array>,
 *     created: string,
 *     updated: string,
 *     associations: array<string, array>,
 *     quantified_associations: array<string, array>,
 * }
 * @psalm-type AkeneoEvent = array{
 *     action: string,
 *     event_id: string,
 *     event_datetime: string,
 *     author: string,
 *     author_type: string,
 *     pim_source: string,
 *     data: array{
 *         resource: AkeneoEventProduct|AkeneoEventProductModel
 *     },
 * }
 * @psalm-type AkeneoEvents = array{
 *     events: AkeneoEvent[],
 * }
 */
final class WebhookController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private MessageBusInterface $messageBus,
        private string $secret,
    ) {
    }

    /**
     * As guideline see the documentation here: https://api.akeneo.com/getting-started/quick-start-my-first-webhook-5x/step-2.html
     *
     * @throws RuntimeException
     * @throws \JsonException
     */
    public function postAction(Request $request): Response
    {
        $timestamp = $request->headers->get('x-akeneo-request-timestamp');
        $signature = $request->headers->get('x-akeneo-request-signature');
        if (null === $timestamp || null === $signature) {
            $this->logger->debug('The hash does not exists on the request! The request is not from Akeneo.');

            return new Response('', Response::HTTP_UNAUTHORIZED);
        }

        $body = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $timestamp . '.' . $body, $this->secret);
        if (false === hash_equals($signature, $expectedSignature)) {
            $this->logger->debug('The hash does not match! The request is not from Akeneo or the secret is wrong.');

            return new Response('', Response::HTTP_UNAUTHORIZED);
        }
        if (time() - (int) $timestamp > 300) {
            $this->logger->debug('The request is too old (> 5min)');

            throw new RuntimeException('Request is too old (> 5min)');
        }

        /**
         * @TODO Could this be improved by using serializer? Is it necessary or overwork?
         *
         * @var AkeneoEvents $akeneoEvents
         */
        $akeneoEvents = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        foreach ($akeneoEvents['events'] as $akeneoEvent) {
            $this->logger->debug(sprintf('Received event %s with id "%s"', $akeneoEvent['action'], $akeneoEvent['event_id']));

            $resource = $akeneoEvent['data']['resource'];
            if (array_key_exists('identifier', $resource)) {
                $productCode = $resource['identifier'];
                $this->logger->debug(sprintf(
                    'Dispatching product import message for %s',
                    $productCode,
                ));
                $this->messageBus->dispatch(new ItemImport(
                    ProductImporter::AKENEO_ENTITY,
                    $productCode,
                ));
                $this->logger->debug(sprintf(
                    'Dispatching product associations import message for %s',
                    $productCode,
                ));
                $this->messageBus->dispatch(new ItemImport(
                    ProductAssociationsImporter::AKENEO_ENTITY,
                    $productCode,
                ));
            }
        }

        return new Response();
    }
}
