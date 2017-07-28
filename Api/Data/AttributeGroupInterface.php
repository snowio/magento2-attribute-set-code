<?php
namespace SnowIO\AttributeSetCode\Api\Data;

interface AttributeGroupInterface
{
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
