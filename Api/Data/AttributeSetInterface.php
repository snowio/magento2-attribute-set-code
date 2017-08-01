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
}
