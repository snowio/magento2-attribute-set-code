<?php

namespace SnowIO\AttributeSetCode\Model;

use Magento\Catalog\Api\AttributeSetManagementInterface;
use Magento\Eav\Api\AttributeGroupRepositoryInterface;
use Magento\Eav\Api\AttributeManagementInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeGroupInterfaceFactory;
use Magento\Eav\Api\Data\AttributeSetInterfaceFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use SnowIO\AttributeSetCode\Api\CodedAttributeSetRepositoryInterface;
use SnowIO\AttributeSetCode\Api\Data\AttributeGroupInterface;
use SnowIO\AttributeSetCode\Api\Data\AttributeSetInterface;

class CodedAttributeSetRepository implements CodedAttributeSetRepositoryInterface
{
    private $attributeSetCodeRepository;
    private $attributeGroupCodeRepository;
    private $attributeGroupRepository;
    private $attributeSetManagement;
    private $attributeSetFactory;
    private $attributeGroupFactory;
    private $attributeManagement;
    private $searchCriteriaBuilder;
    private $attributeRepository;

    public function __construct(
        AttributeGroupCodeRepository $attributeGroupCodeRepository,
        AttributeSetCodeRepository $attributeSetCodeRepository,
        AttributeGroupRepositoryInterface $attributeGroupRepository,
        AttributeSetManagementInterface $attributeSetManagement,
        AttributeSetInterfaceFactory $attributeSetFactory,
        AttributeGroupInterfaceFactory $attributeGroupFactory,
        AttributeManagementInterface $attributeManagement,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        AttributeRepositoryInterface  $attributeRepository
    ) {
        $this->attributeGroupCodeRepository = $attributeGroupCodeRepository;
        $this->attributeSetCodeRepository = $attributeSetCodeRepository;
        $this->attributeGroupRepository = $attributeGroupRepository;
        $this->attributeSetManagement = $attributeSetManagement;
        $this->attributeSetFactory = $attributeSetFactory;
        $this->attributeGroupFactory = $attributeGroupFactory;
        $this->attributeManagement = $attributeManagement;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->attributeRepository = $attributeRepository;
    }

    public function save(AttributeSetInterface $attributeSet)
    {
        //todo: begin DB transaction

        try {
            $attributeSetCode = $attributeSet->getAttributeSetCode();
            $attributeSetId = $this->attributeSetCodeRepository->getAttributeSetId($attributeSetCode);

            if (null === $attributeSetId) {
                $attributeSet = $this->createAttributeSet($attributeSet, 11); //todo look into this id
                $attributeSetId = $attributeSet->getAttributeSetId();
            }

            $inputAttributeGroups = $attributeSet->getAttributeGroups();

            $inputAttributeGroupCodeToIdMap = array_map(function (AttributeGroupInterface $inputAttributeGroup) use (
                $attributeSetId
            ) {
                $attributeGroupCode = $inputAttributeGroup->getAttributeGroupCode();
                return [
                    $inputAttributeGroup->getAttributeGroupCode() =>
                        [
                            'id' => $this->attributeGroupCodeRepository->getAttributeGroupId($attributeGroupCode,
                                $attributeSetId),
                            'group' => $inputAttributeGroup
                        ]
                ];
            }, $inputAttributeGroups);

            //input attribute group code is a map that contains attribute group code -> attribute group id
            $existingAttributeGroupIds = $this->attributeGroupCodeRepository->getAttributeGroupIds($attributeSetCode) ?? [];
            $inputAttributeGroupIds = array_filter(array_column(array_values($inputAttributeGroupCodeToIdMap), 'id'));
            $attributeGroupIdsToRemove = array_diff($existingAttributeGroupIds, $inputAttributeGroupIds);

            $this->removeAttributeGroups($attributeGroupIdsToRemove);

            foreach ($inputAttributeGroupCodeToIdMap as $attributeGroupCode => $attributeGroupData) {
                $attributeGroupId = $attributeGroupData['id'];
                /** @var AttributeGroupInterface $attributeGroup */
                $inputAttributeGroup = $attributeGroupData['group'];
                if ($attributeGroupId === null) {
                    //create an attribute group
                    $attributeGroupId = $this->createAttributeGroup($attributeSetId, $inputAttributeGroup);
                } else {
                    $attributesInGroup = $this->getAttributes($attributeSet->getEntityType(), $attributeGroupId);
                    $this->removeAttributesFromGroup
                }

                $this->assignAttributesInGroup($inputAttributeGroup, $attributeSet->getEntityType(), $attributeSetId,
                    $attributeGroupId);
                //add the attributes that are not in that group to that group

                //remove any attributes that were not specified in the set of attribute
            }

            // todo: commut DB transaction
            return $attributeSet;
        } catch (\Throwable $e) {
            // todo: rollback DB transaction
            throw $e;
        }
    }


    /**
     * Create an attribute group and assign all the attributes in the interface to the group
     * @param AttributeGroupInterface $attributeGroup
     */
    private function createAttributeGroup(int $attributeSetId, AttributeGroupInterface $attributeGroup): int
    {
        $_attributeGroup = $this->attributeGroupFactory->create()
            ->setAttributeGroupId(null)
            ->setAttributeGroupName($attributeGroup->getName())
            ->setSortOrder($attributeGroup->getSortOrder())
            ->setAttributeSetId($attributeSetId);

        $_attributeGroup = $this->attributeGroupRepository->save($_attributeGroup);
        return $_attributeGroup->getAttributeGroupId();
    }

    private function removeAttributeGroups(array $attributeGroupIdsToRemove)
    {
        foreach ($attributeGroupIdsToRemove as $attributeGroupId) {
            $this->attributeGroupRepository->deleteById($attributeGroupId);
        }
    }

    private function createAttributeSet(
        AttributeSetInterface $attributeSet,
        $skeletonId
    ) : \Magento\Eav\Api\Data\AttributeSetInterface {
        $attributeSetCode = $attributeSet->getAttributeSetCode();
        $_attributeSet = $this->attributeSetFactory->create()
            ->setId(null)
            ->setEntityTypeId($attributeSet->getEntityType())
            ->setAttributeSetName($attributeSet->getName())
            ->setSortOrder($attributeSet->getSortOrder());

        $_attributeSet = $this->attributeSetManagement->create($_attributeSet, $skeletonId);
        $this->attributeSetCodeRepository->setAttributeSetId($_attributeSet->getAttributeSetId(), $attributeSetCode);
        return $_attributeSet;
    }

    private function assignAttributesInGroup(
        $inputAttributeGroup,
        $entityType,
        $attributeSetId,
        $attributeGroupId
    ) {
        $sortOrder = 0;
        foreach ($inputAttributeGroup->getAttributes() as $attributeCode) {
            $this->attributeManagement->assign(
                $entityType,
                $attributeSetId,
                $attributeGroupId,
                $attributeCode,
                ++$sortOrder
            );
        }
    }

    private function getAttributes($groupId, $entityTypeId)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(\Magento\Eav\Api\Data\AttributeGroupInterface::GROUP_ID, $groupId)
            ->create();
        return $this->attributeRepository->getList($entityTypeId, $searchCriteria)->getItems();
    }

    private function unassignAttributeNotInGroup(
        $inputAttributeGroup,
        $getEntityType,
        $attributeSetId,
        $attributeGroupId
    ) {

        $attributesNotInGroup = [];




    }
}