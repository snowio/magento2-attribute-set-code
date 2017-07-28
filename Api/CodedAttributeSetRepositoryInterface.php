<?php
namespace SnowIO\AttributeSetCode\Api;

use SnowIO\AttributeSetCode\Api\Data\AttributeSetInterface;

interface CodedAttributeSetRepositoryInterface
{
    /**
     * Save attribute set data
     *
     * @param \SnowIO\AttributeSetCode\Api\Data\AttributeSetInterface $attributeSet
     * @return \Magento\Eav\Api\Data\AttributeSetInterface saved attribute set
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException If attribute set is not found
     */
    public function save(AttributeSetInterface $attributeSet);

}
