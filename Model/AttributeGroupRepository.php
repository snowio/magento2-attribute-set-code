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

    public function save(AttributeGroupInterface $attributeGroupInterface)
    {
        /** @var  \Magento\Eav\Api\Data\AttributeGroupInterface $attributeGroup */
        $attributeGroup = $this->attributeGroupFactory->create([
            'attribute_group_id' => ,
            'attribute_group_name' => ,
            "attribute_set_id" => ,

        ])


        $this->attributeGroupRepository->save($attributeGroup);

    }

    public function delete(string $attributeGroupCode)
    {

    }
}