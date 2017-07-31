<?php
namespace SnowIO\AttributeSetCode\Api\Data;

interface AttributeSetInterface
{
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
