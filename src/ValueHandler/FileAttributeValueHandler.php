<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\ValueHandler;

use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Symfony\Component\Filesystem\Filesystem;
use Webgriffe\SyliusAkeneoPlugin\ApiClientInterface;
use Webgriffe\SyliusAkeneoPlugin\ValueHandlerInterface;
use Webmozart\Assert\Assert;

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

        /** @var ProductInterface|null $product */
        $product = $subject->getProduct();
        Assert::isInstanceOf($product, ProductInterface::class);
        // TODO: we should download all files related to all channels of a product, and not only the first one?
        $mediaCode = $this->getValue($value, $product);
        if ($mediaCode === null) {
            // TODO remove existing image? See https://github.com/webgriffe/SyliusAkeneoPlugin/issues/61
            return;
        }

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

    private function getValue(array $value, ProductInterface $product): ?string
    {
        $productChannelCodes = array_map(static function (ChannelInterface $channel): ?string {
            return $channel->getCode();
        }, $product->getChannels()->toArray());
        foreach ($value as $valueData) {
            if (!is_array($valueData)) {
                throw new \InvalidArgumentException(sprintf('Invalid Akeneo value data: expected an array, "%s" given.', gettype($valueData)));
            }
            // todo: we should throw here? it seeme that API won't never return an empty array
            if (!array_key_exists('data', $valueData)) {
                continue;
            }

            if (!array_key_exists('scope', $valueData)) {
                throw new \InvalidArgumentException('Invalid Akeneo value data: required "scope" information was not found.');
            }
            if ($valueData['scope'] !== null && !in_array($valueData['scope'], $productChannelCodes, true)) {
                continue;
            }

            /** @psalm-suppress MixedAssignment */
            $data = $valueData['data'];
            if (!is_string($data) && null !== $data) {
                throw new \InvalidArgumentException(sprintf('Invalid Akeneo value data: expected a string or null value, got "%s".', gettype($data)));
            }

            return $data;
        }

        throw new \InvalidArgumentException('Invalid Akeneo attachment data: cannot find the media code.');
    }
}
