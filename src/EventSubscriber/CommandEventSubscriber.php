<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\EventSubscriber;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Webgriffe\SyliusAkeneoPlugin\TemporaryFilesManagerInterface;

final class CommandEventSubscriber implements EventSubscriberInterface
{
    /** @var TemporaryFilesManagerInterface */
    private $temporaryFilesManager;

    public function __construct(TemporaryFilesManagerInterface $temporaryFilesManager)
    {
        $this->temporaryFilesManager = $temporaryFilesManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [ConsoleEvents::TERMINATE => ['onTerminateCommand']];
    }

    public function onTerminateCommand(ConsoleTerminateEvent $event): void
    {
        $command = $event->getCommand();

        if ($command !== null && $command->getName() === 'webgriffe:akeneo:consume') {
            $this->temporaryFilesManager->deleteAllTemporaryFiles();
        }
    }
}
