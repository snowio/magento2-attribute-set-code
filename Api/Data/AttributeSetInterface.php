<?php
namespace SnowIO\AttributeSetCode\Api\Data;

interface AttributeSetInterface
{
    const CODE = 'code';
    const NAME = 'name';
    const SORT_ORDER = 'sort_order';
    const ATTRIBUTE_GROUPS = ' attribute_groups';
    const ENTITY_TYPE = 'entity_type';

    /**
     * @return string
     */
    public function getAttributeSetCode();

    /**
     * @return string
     */
    public function getName();

    /**
     * @return int
     */
    public function getSortOrder();

    /**
     * @return null|AttributeGroupInterface[]
     */
    public function getAttributeGroups();

    /**
     * @return int
     */
    public function getEntityType();
}
