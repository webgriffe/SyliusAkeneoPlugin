<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\Middleware;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Throwable;
use Webgriffe\SyliusAkeneoPlugin\Entity\ItemImportResult;
use Webgriffe\SyliusAkeneoPlugin\Message\ItemImport;
use Webgriffe\SyliusAkeneoPlugin\MessageHandler\Exception\ItemImportException;
use Webgriffe\SyliusAkeneoPlugin\Respository\ItemImportResultRepositoryInterface;

final class ItemImportResultPersisterMiddleware implements MiddlewareInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ItemImportResultRepositoryInterface $itemImportResultRepository,
        private LoggerInterface $logger,
    ) {
    }

    #[\Override]
    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $message = $envelope->getMessage();

        try {
            $result = $stack->next()->handle($envelope, $stack);

            if (!$message instanceof ItemImport) {
                return $result;
            }

            if (null === $envelope->last(ReceivedStamp::class)) {
                return $result;
            }

            $this->itemImportResultRepository->add(
                new ItemImportResult(
                    $message->getAkeneoEntity(),
                    $message->getAkeneoIdentifier(),
                    true,
                    sprintf(
                        'Successfully imported item "%s" with identifier "%s" from Akeneo.',
                        $message->getAkeneoEntity(),
                        $message->getAkeneoIdentifier(),
                    ),
                ),
            );

            return $result;
        } catch (Throwable $exception) {
            if (!$message instanceof ItemImport) {
                throw $exception;
            }

            $itemImportException = $exception;
            if (!$exception instanceof ItemImportException) {
                $itemImportException = new ItemImportException(
                    sprintf(
                        'There has been an error while importing "%s" entity from Akeneo with identifier "%s": %s',
                        $message->getAkeneoEntity(),
                        $message->getAkeneoIdentifier(),
                        $exception->getMessage(),
                    ),
                    0,
                    $exception,
                );
            }
            $this->logger->error($itemImportException->getMessage(), ['exception' => $itemImportException]);

            if (!$this->isEntityManagerAbleToWrite()) {
                throw $itemImportException;
            }

            $this->entityManager->clear();
            $this->itemImportResultRepository->add(
                new ItemImportResult(
                    $message->getAkeneoEntity(),
                    $message->getAkeneoIdentifier(),
                    false,
                    $itemImportException->getMessage(),
                ),
            );

            throw $itemImportException;
        }
    }

    private function isEntityManagerAbleToWrite(): bool
    {
        if (!$this->entityManager->isOpen()) {
            return false;
        }

        /**
         * This check is needed because when configured with sync transport the following is the middlewares call chain:
         *
         * 1. MessageBusInterface::dispatch(ItemImport)
         *  -> 2. ItemImportResultPersisterMiddleware
         *    -> 3. DoctrineTransactionMiddleware // Here the middleware starts the first transaction
         *      -> 4. SyncTransport::send()
         *        -> 5. ItemImportResultPersisterMiddleware
         *          -> 6. DoctrineTransactionMiddleware // Here the middleware starts the second transaction
         *            -> 7. ItemImportHandler // Here the exception is thrown
         *
         * When the exception is thrown the middleware chain continue in the reverse order, so:
         *
         *          <- 8. DoctrineTransactionMiddleware // Here the middleware rollbacks the second transaction so the
         *                                              // first transaction is still active and the connection is
         *                                              // marked as rollback only.
         *        <- 9. ItemImportResultPersisterMiddleware // Since the connection is marked as rollback only we cannot
         *                                                  // persist anything to the database.
         *     <- 10. DoctrineTransactionMiddleware // Here the middleware rollbacks the first transaction so the
         *                                          // connection is not "rollback-only" anymore
         *   <- 11. ItemImportResultPersisterMiddleware // Here the middleware can persist the error to the database
         */
        if ($this->entityManager->getConnection()->isTransactionActive() &&
            $this->entityManager->getConnection()->isRollbackOnly()) {
            return false;
        }

        return true;
    }
}
