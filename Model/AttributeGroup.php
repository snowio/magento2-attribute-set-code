<?php

namespace SnowIO\AttributeSetCode\Model;

use Magento\Framework\DataObject;
use SnowIO\AttributeSetCode\Api\Data\AttributeGroupInterface;

class AttributeGroup extends DataObject implements AttributeGroupInterface
{

    public function getAttributeGroupCode()
    {
        return $this->getData(AttributeGroupInterface::ATTRIBUTE_GROUP_CODE);
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

    public function setAttributeGroupCode($attributeGroupCode)
    {
        return $this->setData(AttributeGroupInterface::ATTRIBUTE_GROUP_CODE, $attributeGroupCode);
    }

    public function setName($name)
    {
        return $this->setData(AttributeGroupInterface::NAME, $name);
    }

    public function setSortOrder($sortOrder)
    {
        return $this->setData(AttributeGroupInterface::SORT_ORDER, $sortOrder);
    }

    public function setAttributes($attributes)
    {
        return $this->setData(AttributeGroupInterface::ATTRIBUTES, $attributes);
    }
}