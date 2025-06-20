<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\EventSubscriber;

use Sylius\Component\Core\Model\ImagesAwareInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Webmozart\Assert\Assert;

final class ProductEventSubscriber implements EventSubscriberInterface
{
    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            'sylius.product.pre_create' => ['removeImagesFileProperty', -50],
            'sylius.product.pre_update' => ['removeImagesFileProperty', -50],
        ];
    }

    /**
     * When more than two variants with different images are handled by the same instance of Messenger
     * the file property should be removed after having uploaded with the Sylius\Bundle\CoreBundle\EventListener\ImagesUploadListener.
     * Otherwise, the next pre create/update product event will throw an error by getting content from the first image that was removed by the TemporaryFilesManager.
     * See features/importing_products_from_queue.feature for having a real case.
     */
    public function removeImagesFileProperty(GenericEvent $event): void
    {
        /** @var ImagesAwareInterface|mixed $subject */
        $subject = $event->getSubject();
        Assert::isInstanceOf($subject, ImagesAwareInterface::class);

        foreach ($subject->getImages() as $image) {
            $image->setFile(null);
        }
    }
}
