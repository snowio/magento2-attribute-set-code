<?php

namespace SnowIO\AttributeSetCode\Model;

use Magento\Framework\DataObject;
use SnowIO\AttributeSetCode\Api\Data\AttributeGroupInterface;
use SnowIO\AttributeSetCode\Api\Data\AttributeSetInterface;

class AttributeSet extends DataObject implements AttributeSetInterface
{

    /**
     * @return string
     */
    public function getAttributeSetCode()
    {
        return $this->getData(AttributeSetInterface::CODE);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->getData(AttributeSetInterface::NAME);
    }

    /**
     * @return int
     */
    public function getSortOrder()
    {
        return $this->getData(AttributeSetInterface::SORT_ORDER);
    }

    /**
     * @return null|AttributeGroupInterface[]
     */
    public function getAttributeGroups()
    {
        return $this->getData(AttributeSetInterface::ATTRIBUTE_GROUPS);
    }

    /**
     * @return int
     */
    public function getEntityType()
    {
        return $this->getData(AttributeSetInterface::ENTITY_TYPE);
    }
}