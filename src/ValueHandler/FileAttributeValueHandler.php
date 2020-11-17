<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\ValueHandler;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Exception\HttpException;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Webgriffe\SyliusAkeneoPlugin\ValueHandlerInterface;
use Webmozart\Assert\Assert;

class FileAttributeValueHandler implements ValueHandlerInterface
{
    public const AKENEO_ATTRIBUTE_TYPE_FILE = 'pim_catalog_file';

    /** @var AkeneoPimClientInterface */
    private $apiClient;

    /** @var Filesystem */
    private $filesystem;

    /** @var string */
    private $akeneoAttributeCode;

    /** @var string */
    private $downloadPath;

    public function __construct(
        AkeneoPimClientInterface $apiClient,
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

        try {
            $attributeInfo = $this->apiClient->getAttributeApi()->get($attribute);
        } catch (HttpException $e) {
            if ($e->getResponse()->getStatusCode() === 404) {
                throw new \InvalidArgumentException(sprintf('The attribute "%s" does not exists.', $attribute));
            }

            throw $e;
        }
        if (
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
        $mediaCode = $value[0]['data'] ?? null;
        if (!is_string($mediaCode)) {
            throw new \InvalidArgumentException('Invalid Akeneo attachment data. Cannot find the media code.');
        }
        $downloadedFile = $this->downloadFile($mediaCode);

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

    private function downloadFile(string $mediaCode): \SplFileInfo
    {
        $response = $this->apiClient->getProductMediaFileApi()->download($mediaCode);
        $statusClass = (int) ($response->getStatusCode() / 100);
        $bodyContents = $response->getBody()->getContents();
        if ($statusClass !== 2) {
            $responseResult = json_decode($bodyContents, true);

            throw new \HttpException($responseResult['message'], $responseResult['code']);
        }
        $tempName = tempnam(sys_get_temp_dir(), 'akeneo-');
        Assert::string($tempName);
        file_put_contents($tempName, $bodyContents);

        return new File($tempName);
    }
}
