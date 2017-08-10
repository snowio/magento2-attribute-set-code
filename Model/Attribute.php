<?php
namespace SnowIO\AttributeSetCode\Model;

use Magento\Framework\DataObject;
use SnowIO\AttributeSetCode\Api\Data\AttributeInterface;

class Attribute extends DataObject implements AttributeInterface
{
    public function getAttributeCode()
    {
        return $this->getData(AttributeInterface::ATTRIBUTE_CODE);
    }

    public function getSortOrder()
    {
        $sortOrder = $this->getData(AttributeInterface::SORT_ORDER);
        return $sortOrder ? (int) $sortOrder : null;
    }

    public function setAttributeCode($attributeCode)
    {
        return $this->setData(AttributeInterface::ATTRIBUTE_CODE, $attributeCode);
    }

    public function setSortOrder($sortOrder)
    {
        return $this->setData(AttributeInterface::SORT_ORDER, $sortOrder);
    }
}
