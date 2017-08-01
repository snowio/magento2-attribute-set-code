<?php

namespace SnowIO\AttributeSetCode\Model;

use Magento\Framework\DataObject;
use SnowIO\AttributeSetCode\Api\Data\AttributeGroupInterface;

class AttributeGroup extends DataObject implements AttributeGroupInterface
{

    public function getAttributeGroupCode()
    {
        return $this->getData(AttributeGroupInterface::CODE);
    }

    public function getName()
    {
        return $this->getData(AttributeGroupInterface::NAME);
    }

    public function getSortOrder()
    {
        return $this->getData(AttributeGroupInterface::SORT_ORDER);
    }

    public function getAttributes()
    {
        return $this->getData(AttributeGroupInterface::ATTRIBUTES);
    }
}