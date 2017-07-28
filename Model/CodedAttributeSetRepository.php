<?php

namespace SnowIO\AttributeSetCode\Model;

use Magento\Catalog\Api\AttributeSetRepositoryInterface;
use Magento\Eav\Api\AttributeGroupRepositoryInterface;
use SnowIO\AttributeSetCode\Api\CodedAttributeSetRepositoryInterface;
use SnowIO\AttributeSetCode\Api\Data\AttributeGroupInterface;
use SnowIO\AttributeSetCode\Api\Data\AttributeSetInterface;

class CodedAttributeSetRepository implements CodedAttributeSetRepositoryInterface
{
    private $attributeSetRepository;
    private $attributeSetCodeRepository;
    private $attributeGroupCodeRepository;
    private $attributeGroupRepository;

    public function __construct(
        AttributeSetRepositoryInterface $attributeSetRepository,
        AttributeGroupCodeRepository $attributeGroupCodeRepository,
        AttributeSetCodeRepository $attributeSetCodeRepository,
        AttributeGroupRepositoryInterface $attributeGroupRepository,
        ProductAttributeGroupFactory $attributeGroupFactory
    ) {
        $this->attributeSetRepository = $attributeSetRepository;
        $this->attributeGroupCodeRepository = $attributeGroupCodeRepository;
        $this->attributeSetCodeRepository = $attributeSetCodeRepository;
        $this->attributeGroupRepository = $attributeGroupRepository;
    }

    public function save(AttributeSetInterface $attributeSet)
    {
        $attributeSetCode = $attributeSet->getAttributeSetCode();
        $inputAttributeGroups = $attributeSet->getAttributeGroups();

        $inputAttributeGroupCodeToIdMap = array_map(function (AttributeGroupInterface $attributeGroup) {
            return [$attributeGroup->getAttributeGroupCode() => ['id' => $this->attributeGroupCodeRepository->getAttributeGroupId($attributeGroup), 'group' => $attributeGroup]];
        }, $inputAttributeGroups);

        //input attribute group code is a map that contains attribute group code - attribute group id
        //if the id is null then we will need to create the group

        $allAttributeGroupIdsInAttributeSet = $this->attributeGroupCodeRepository->getAttributeGroupIds($attributeSetCode) ?? [];
        $attributeGroupIdsToRemove = array_diff(array_filter(array_values($inputAttributeGroupCodeToIdMap)), $allAttributeGroupIdsInAttributeSet);

        foreach ($attributeGroupIdsToRemove as $attributeGroupId) {
            $this->attributeGroupRepository->deleteById($attributeGroupId);
        }

        foreach ($inputAttributeGroupCodeToIdMap as $attributeGroupCode => $attributeGroupData) {
            $attributeGroupId = $attributeGroupData['id'];
            if ($attributeGroupId === null) {
                //create an attribute group
                $this->createAttributeGroup($attributeGroupData['group']);
            }

            //add the attributes that are not in that group to that group


            //remove any attributes that were not specified in the set of attributes


        }
        //in the group for any attributes that don't exist create them
        //in the group for any attributes not specified remove them
        return $attributeSet;
    }

    private function createAttributeGroup(AttributeGroupInterface $attributeGroup)
    {
        $attribute
    }
}