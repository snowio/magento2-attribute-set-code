<?php

namespace SnowIO\AttributeSetCode\Model;

use Magento\Framework\DataObject;
use SnowIO\AttributeSetCode\Api\Data\AttributeSetInterface;

class AttributeSet extends DataObject implements AttributeSetInterface
{

    public function getAttributeSetCode()
    {
        return $this->getData(AttributeSetInterface::CODE);
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
}