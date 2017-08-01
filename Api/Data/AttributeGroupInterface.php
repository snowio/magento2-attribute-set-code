<?php
namespace SnowIO\AttributeSetCode\Api\Data;

interface AttributeGroupInterface
{
    const CODE = 'code';
    const NAME = 'name';
    const SORT_ORDER = 'sort_order';
    const ATTRIBUTES = 'attributes';

    /**
     * @return null|string
     */
    public function getAttributeGroupCode();

    /**
     * @return null|string
     */
    public function getName();

    /**
     * @return null|int
     */
    public function getSortOrder();

    /**
     * @return null|string[]
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
     * @param string[] $attributes
     * @return \SnowIO\AttributeSetCode\Api\Data\AttributeGroupInterface
     */
    public function setAttributes($attributes);
}
