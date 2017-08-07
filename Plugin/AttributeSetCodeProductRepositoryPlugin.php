<?php

namespace SnowIO\AttributeSetCode\Plugin;

use Magento\Catalog\Api\Data\ProductInterface;

class AttributeSetCodeProductRepositoryPlugin
{
    private $attributeSetCodeRepository;

    public function __construct(\SnowIO\AttributeSetCode\Model\AttributeSetCodeRepository $attributeSetCodeRepository)
    {
        $this->attributeSetCodeRepository = $attributeSetCodeRepository;
    }

    public function beforeSave(
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        ProductInterface $product,
        $saveOptions = false
    ) {
        if (!$extensionAttributes = $product->getExtensionAttributes()) {
            return [$product, $saveOptions];
        }

        if (null === ($attributeSetCode = $extensionAttributes->getAttributeSetCode())) {
            return [$product, $saveOptions];
        }
        $attributeSetId = $this->attributeSetCodeRepository->getAttributeSetId(4, $attributeSetCode);
        $product->setAttributeSetId($attributeSetId);

        return [$product, $saveOptions];
    }
}