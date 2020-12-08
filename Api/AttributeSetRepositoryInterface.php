<?php
namespace SnowIO\AttributeSetCode\Api;

use SnowIO\AttributeSetCode\Api\Data\AttributeSetInterface;

interface AttributeSetRepositoryInterface
{
    /**
     * Save attribute set data
     *
     * @param AttributeSetInterface $attributeSet
     * @return void
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException If attribute set is not found
     */
    public function save(AttributeSetInterface $attributeSet);

}
