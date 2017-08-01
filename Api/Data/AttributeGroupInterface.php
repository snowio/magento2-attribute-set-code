<?php
namespace SnowIO\AttributeSetCode\Api\Data;

interface AttributeGroupInterface
{
    const CODE = 'code';
    const NAME = 'name';
    const SORT_ORDER = 'sort_order';
    const ATTRIBUTES = 'attributes';

    /**
     * @return string
     */
    public function getAttributeGroupCode();

    /**
     * @return string
     */
    public function getName();

    /**
     * @return int
     */
    public function getSortOrder();

    /**
     * @return null|string[]
     */
    public function getAttributes();
}
