<?php

namespace SnowIO\AttributeSetCode\Model;

use Magento\Framework\DataObject;
use SnowIO\AttributeSetCode\Api\Data\AttributeSetInterface;

class AttributeSet extends DataObject implements AttributeSetInterface
{

    public function getAttributeSetCode()
    {
        return $this->getData(AttributeSetInterface::ATTRIBUTE_SET_CODE);
    }

    public function getName()
    {
        return $this->getData(AttributeSetInterface::NAME);
    }

    public function getSortOrder()
    {
        return $this->getData(AttributeSetInterface::SORT_ORDER);
    }

    public function getAttributeGroups()
    {
        return $this->getData(AttributeSetInterface::ATTRIBUTE_GROUPS);
    }

    public function getEntityTypeCode()
    {
        return $this->getData(AttributeSetInterface::ENTITY_TYPE_CODE);
    }

    public function setAttributeSetCode($code)
    {
        return $this->setData(AttributeSetInterface::ATTRIBUTE_SET_CODE, $code);
    }

    public function setName($name)
    {
        return $this->setData(AttributeSetInterface::NAME, $name);
    }

    public function setSortOrder($sortOrder)
    {
        return $this->setData(AttributeSetInterface::SORT_ORDER, $sortOrder);
    }

    public function setAttributeGroups($attributeGroups)
    {
        return $this->setData(AttributeSetInterface::ATTRIBUTE_GROUPS, $attributeGroups);
    }

    public function setEntityTypeCode($entityTypeCode)
    {
        return $this->setData(AttributeSetInterface::ENTITY_TYPE_CODE, $entityTypeCode);
    }
}