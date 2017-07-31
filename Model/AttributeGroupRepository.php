<?php

namespace SnowIO\AttributeSetCode\Model;

use Magento\Eav\Api\AttributeGroupRepositoryInterface;
use SnowIO\AttributeSetCode\Api\Data\AttributeGroupInterface;

class AttributeSetRepository
{
    private $attributeGroupFactory;
    private $attributeGroupRepository;

    public function __construct(
        \Magento\Catalog\Model\ProductAttributeGroupFactory $attributeGroupFactory,
        AttributeGroupRepositoryInterface $attributeGroupRepository
    ) {
        $this->attributeGroupFactory = $attributeGroupFactory;
        $this->attributeGroupRepository = $attributeGroupRepository;
    }

}