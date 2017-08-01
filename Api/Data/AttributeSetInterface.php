<?php
namespace SnowIO\AttributeSetCode\Api\Data;

interface AttributeSetInterface
{
    const CODE = 'code';
    const NAME = 'name';
    const SORT_ORDER = 'sort_order';
    const ATTRIBUTE_GROUPS = ' attribute_groups';
    const ENTITY_TYPE_CODE = 'entity_type_code';

    /**
     * @return null|string
     */
    public function getAttributeSetCode();

    /**
     * @return null|string
     */
    public function getName();

    /**
     * @return null|int
     */
    public function getSortOrder();

    /**
     * @return null|AttributeGroupInterface[]
     */
    public function getAttributeGroups();

    /**
     * @return null|string
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
