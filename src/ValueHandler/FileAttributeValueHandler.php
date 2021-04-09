<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\ValueHandler;

use Sylius\Component\Core\Model\ProductVariantInterface;
use Symfony\Component\Filesystem\Filesystem;
use Webgriffe\SyliusAkeneoPlugin\ApiClientInterface;
use Webgriffe\SyliusAkeneoPlugin\ValueHandlerInterface;

final class FileAttributeValueHandler implements ValueHandlerInterface
{
    public const AKENEO_ATTRIBUTE_TYPE_FILE = 'pim_catalog_file';

    /** @var ApiClientInterface */
    private $apiClient;

    /** @var Filesystem */
    private $filesystem;

    /** @var string */
    private $akeneoAttributeCode;

    /** @var string */
    private $downloadPath;

    public function __construct(
        ApiClientInterface $apiClient,
        Filesystem $filesystem,
        string $akeneoAttributeCode,
        string $downloadPath
    ) {
        $this->apiClient = $apiClient;
        $this->filesystem = $filesystem;
        $this->akeneoAttributeCode = $akeneoAttributeCode;
        $this->downloadPath = $downloadPath;
    }

    public function supports($subject, string $attribute, array $value): bool
    {
        if (!$subject instanceof ProductVariantInterface) {
            return false;
        }
        if ($attribute !== $this->akeneoAttributeCode) {
            return false;
        }
        $attributeInfo = $this->apiClient->findAttribute($attribute);
        if (
            $attributeInfo === null ||
            !array_key_exists('type', $attributeInfo) ||
            $attributeInfo['type'] !== self::AKENEO_ATTRIBUTE_TYPE_FILE
        ) {
            throw new \InvalidArgumentException(
                sprintf(
                    'This file value handler only supports akeneo file attributes. %s is not a file attribute',
                    $attribute
                )
            );
        }

        return true;
    }

    public function handle($subject, string $attribute, array $value): void
    {
        if (!$subject instanceof ProductVariantInterface) {
            throw new \InvalidArgumentException(
                sprintf(
                    'This file value handler only supports instances of %s, %s given.',
                    ProductVariantInterface::class,
                    is_object($subject) ? get_class($subject) : gettype($subject)
                )
            );
        }

        if (!array_key_exists(0, $value) || !is_array($value[0]) || !array_key_exists('data', $value[0])) {
            throw new \InvalidArgumentException('Invalid Akeneo attachment data. Cannot find the media code.');
        }

        if ($value[0]['data'] === null) {
            // TODO remove existing image? See https://github.com/webgriffe/SyliusAkeneoPlugin/issues/61
            return;
        }

        /** @var string $mediaCode */
        $mediaCode = $value[0]['data'];
        $downloadedFile = $this->apiClient->downloadFile($mediaCode);

        $relativeFilePath = $mediaCode;
        $this->moveFileToAttachmentFolder($relativeFilePath, $downloadedFile);
    }

    private function moveFileToAttachmentFolder(string $relativeFilePath, \SplFileInfo $downloadedFile): void
    {
        $destinationFilepath = sprintf('%s/%s', rtrim($this->downloadPath, '/'), ltrim($relativeFilePath, '/'));
        $destinationFolder = substr($destinationFilepath, 0, (int) strrpos($destinationFilepath, '/'));
        if (!$this->filesystem->exists($destinationFolder)) {
            $this->filesystem->mkdir($destinationFolder);
        }
        $this->filesystem->rename($downloadedFile->getPathname(), $destinationFilepath, true);
    }
}
