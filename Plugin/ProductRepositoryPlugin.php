<?php

namespace SnowIO\AttributeSetCode\Plugin;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

class ProductRepositoryPlugin
{
    private \SnowIO\AttributeSetCode\Model\AttributeSetCodeRepository $attributeSetCodeRepository;

    public function __construct(\SnowIO\AttributeSetCode\Model\AttributeSetCodeRepository $attributeSetCodeRepository)
    {
        $this->attributeSetCodeRepository = $attributeSetCodeRepository;
    }

    public function beforeSave(
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        ProductInterface $product,
        $saveOptions = false
    ) {
        if ($product->getAttributeSetId() !== null) {
            return [$product, $saveOptions];
        }

        if (!$extensionAttributes = $product->getExtensionAttributes()) {
            return [$product, $saveOptions];
        }

        if (null === ($attributeSetCode = $extensionAttributes->getAttributeSetCode())) {
            return [$product, $saveOptions];
        }
        $attributeSetId = $this->attributeSetCodeRepository->getAttributeSetId(4, $attributeSetCode);
        if ($attributeSetId === null) {
            throw new LocalizedException(new Phrase("The specified attribute set code %1 does not exist", [$attributeSetCode]));
        }
        $product->setAttributeSetId($attributeSetId);

        return [$product, $saveOptions];
    }
}
