<?php
namespace SnowIO\AttributeSetCode\Api\Data;

interface AttributeSetInterface
{
    const ATTRIBUTE_SET_CODE = 'attribute_set_code';
    const NAME = 'name';
    const SORT_ORDER = 'sort_order';
    const ATTRIBUTE_GROUPS = ' attribute_groups';
    const ENTITY_TYPE_CODE = 'entity_type_code';

    /**
     * @return string|null
     */
    public function getAttributeSetCode();

    /**
     * @return string|null
     */
    public function getName();

    /**
     * @return int|null
     */
    public function getSortOrder();

    /**
     * @return \SnowIO\AttributeSetCode\Api\Data\AttributeGroupInterface[]|null
     */
    public function getAttributeGroups();

    /**
     * @return string|null
     */
    public function getEntityTypeCode();

    /**
     * @param string $attributeSetCode
     * @return \SnowIO\AttributeSetCode\Api\Data\AttributeSetInterface
     */
    public function setAttributeSetCode($attributeSetCode);

    /**
     * @param string $name
     * @return \SnowIO\AttributeSetCode\Api\Data\AttributeSetInterface
     */
    public function setName($name);

    /**
     * @param int $sortOrder
     * @return \SnowIO\AttributeSetCode\Api\Data\AttributeSetInterface
     */
    public function setSortOrder($sortOrder);

    /**
     * @param \SnowIO\AttributeSetCode\Api\Data\AttributeGroupInterface[] $attributeGroups
     * @return \SnowIO\AttributeSetCode\Api\Data\AttributeSetInterface
     */
    public function setAttributeGroups($attributeGroups);

    /**
     * @param string $entityTypeCode
     * @return \SnowIO\AttributeSetCode\Api\Data\AttributeSetInterface
     */
    public function setEntityTypeCode($entityTypeCode);
    
}
