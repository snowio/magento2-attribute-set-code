<?php
namespace SnowIO\AttributeSetCode\Api\Data;

interface AttributeGroupInterface
{
    public const ATTRIBUTE_GROUP_CODE = 'attribute_group_code';
    public const NAME = 'name';
    public const SORT_ORDER = 'sort_order';
    public const ATTRIBUTES = 'attributes';

    /**
     * @return string|null
     */
    public function getAttributeGroupCode();

    /**
     * @return string|null
     */
    public function getName();

    /**
     * @return int|null
     */
    public function getSortOrder();

    /**
     * @return \SnowIO\AttributeSetCode\Api\Data\AttributeInterface[]|null
     */
    public function getAttributes();

    /**
     * @param string $attributeGroupCode
     * @return \SnowIO\AttributeSetCode\Api\Data\AttributeGroupInterface
     */
    public function setAttributeGroupCode($attributeGroupCode);

    /**
     * @param string $name
     * @return \SnowIO\AttributeSetCode\Api\Data\AttributeGroupInterface
     */
    public function setName($name);

    /**
     * @param int $sortOrder
     * @return \SnowIO\AttributeSetCode\Api\Data\AttributeGroupInterface
     */
    public function setSortOrder($sortOrder);

    /**
     * @param \SnowIO\AttributeSetCode\Api\Data\AttributeInterface[] $attributes
     * @return \SnowIO\AttributeSetCode\Api\Data\AttributeGroupInterface
     */
    public function setAttributes($attributes);
}
