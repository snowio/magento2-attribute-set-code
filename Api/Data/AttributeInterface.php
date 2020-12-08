<?php
namespace SnowIO\AttributeSetCode\Api\Data;

interface AttributeInterface
{
    public const ATTRIBUTE_CODE = 'attribute_code';
    public const SORT_ORDER = 'sort_order';

    /**
     * @return string|null
     */
    public function getAttributeCode();

    /**
     * @return int|null
     */
    public function getSortOrder();

    /**
     * @param string $attributeCode
     * @return \SnowIO\AttributeSetCode\Api\Data\AttributeInterface
     */
    public function setAttributeCode($attributeCode);

    /**
     * @param int $sortOrder
     * @return \SnowIO\AttributeSetCode\Api\Data\AttributeInterface
     */
    public function setSortOrder($sortOrder);
}
