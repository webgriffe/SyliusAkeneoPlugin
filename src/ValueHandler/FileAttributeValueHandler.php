<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\ValueHandler;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Exception\HttpException;
use InvalidArgumentException;
use SplFileInfo;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpKernel\Exception\HttpException as SymfonyHttpException;
use Webgriffe\SyliusAkeneoPlugin\ValueHandlerInterface;
use Webmozart\Assert\Assert;

final class FileAttributeValueHandler implements ValueHandlerInterface
{
    public const AKENEO_ATTRIBUTE_TYPE_FILE = 'pim_catalog_file';

    public function __construct(
        private AkeneoPimClientInterface $apiClient,
        private Filesystem $filesystem,
        private string $akeneoAttributeCode,
        private string $downloadPath,
    ) {
    }

    public function supports($subject, string $attribute, array $value): bool
    {
        if (!$subject instanceof ProductVariantInterface) {
            return false;
        }
        if ($attribute !== $this->akeneoAttributeCode) {
            return false;
        }

        try {
            $attributeInfo = $this->apiClient->getAttributeApi()->get($attribute);
        } catch (HttpException $e) {
            if ($e->getResponse()->getStatusCode() === 404) {
                throw new InvalidArgumentException(sprintf('The attribute "%s" does not exists.', $attribute));
            }

            throw $e;
        }
        if (
            !array_key_exists('type', $attributeInfo) ||
            $attributeInfo['type'] !== self::AKENEO_ATTRIBUTE_TYPE_FILE
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'This file value handler only supports akeneo file attributes. %s is not a file attribute',
                    $attribute,
                ),
            );
        }

        return true;
    }

    public function handle($subject, string $attribute, array $value): void
    {
        if (!$subject instanceof ProductVariantInterface) {
            throw new InvalidArgumentException(
                sprintf(
                    'This file value handler only supports instances of %s, %s given.',
                    ProductVariantInterface::class,
                    get_debug_type($subject),
                ),
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
        $downloadedFile = $this->downloadFile($mediaCode);

        $relativeFilePath = $mediaCode;
        $this->moveFileToAttachmentFolder($relativeFilePath, $downloadedFile);
    }

    private function moveFileToAttachmentFolder(string $relativeFilePath, SplFileInfo $downloadedFile): void
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
        $productChannelCodes = array_map(static fn (ChannelInterface $channel): ?string => $channel->getCode(), $product->getChannels()->toArray());
        foreach ($value as $valueData) {
            if (!is_array($valueData)) {
                throw new InvalidArgumentException(sprintf('Invalid Akeneo value data: expected an array, "%s" given.', gettype($valueData)));
            }
            // todo: we should throw here? it seem that API won't never return an empty array
            if (!array_key_exists('data', $valueData)) {
                continue;
            }

            if (!array_key_exists('scope', $valueData)) {
                throw new InvalidArgumentException('Invalid Akeneo value data: required "scope" information was not found.');
            }
            if ($valueData['scope'] !== null && !in_array($valueData['scope'], $productChannelCodes, true)) {
                continue;
            }

            /** @psalm-suppress MixedAssignment */
            $data = $valueData['data'];
            if (!is_string($data) && null !== $data) {
                throw new InvalidArgumentException(sprintf('Invalid Akeneo value data: expected a string or null value, got "%s".', gettype($data)));
            }

            return $data;
        }

        throw new InvalidArgumentException('Invalid Akeneo attachment data: cannot find the media code.');
    }

    private function downloadFile(string $mediaCode): SplFileInfo
    {
        $response = $this->apiClient->getProductMediaFileApi()->download($mediaCode);
        $statusClass = (int) ($response->getStatusCode() / 100);
        $bodyContents = $response->getBody()->getContents();
        if ($statusClass !== 2) {
            /** @var array $responseResult */
            $responseResult = json_decode($bodyContents, true, 512, \JSON_THROW_ON_ERROR);

            throw new SymfonyHttpException((int) $responseResult['code'], (string) $responseResult['message']);
        }
        $tempName = tempnam(sys_get_temp_dir(), 'akeneo-');
        Assert::string($tempName);
        file_put_contents($tempName, $bodyContents);

        return new File($tempName);
    }
}
