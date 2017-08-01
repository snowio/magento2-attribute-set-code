<?php

namespace SnowIO\AttributeSetCode\Model;

use Magento\Framework\DataObject;
use SnowIO\AttributeSetCode\Api\Data\AttributeGroupInterface;

class AttributeGroup extends DataObject implements AttributeGroupInterface
{

    /**
     * @return string
     */
    public function getAttributeGroupCode()
    {
        return $this->getData(AttributeGroupInterface::CODE);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->getData(AttributeGroupInterface::NAME);
    }

    /**
     * @return int
     */
    public function getSortOrder()
    {
        return $this->getData(AttributeGroupInterface::SORT_ORDER);
    }

    /**
     * @return null|string[]
     */
    public function getAttributes()
    {
        return $this->getData(AttributeGroupInterface::ATTRIBUTES);
    }
}