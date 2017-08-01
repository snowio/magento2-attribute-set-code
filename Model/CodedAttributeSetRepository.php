<?php

namespace SnowIO\AttributeSetCode\Model;

use Magento\Catalog\Api\AttributeSetManagementInterface;
use Magento\Eav\Api\AttributeGroupRepositoryInterface;
use Magento\Eav\Api\AttributeManagementInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeGroupInterfaceFactory;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Api\Data\AttributeSetInterfaceFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ResourceConnection;
use SnowIO\AttributeSetCode\Api\CodedAttributeSetRepositoryInterface;
use SnowIO\AttributeSetCode\Api\Data\AttributeGroupInterface;
use SnowIO\AttributeSetCode\Api\Data\AttributeSetInterface;

class CodedAttributeSetRepository implements CodedAttributeSetRepositoryInterface
{
    private $attributeSetCodeRepository;
    private $attributeGroupCodeRepository;
    private $attributeGroupRepository;
    private $attributeSetManagement;
    /** @var AttributeSetInterfaceFactory */
    private $attributeSetFactory;
    private $attributeGroupFactory;
    private $attributeManagement;
    private $searchCriteriaBuilder;
    private $attributeRepository;
    private $resourceConnection;
    private $entityTypeCodeRepository;

    public function __construct(
        AttributeGroupCodeRepository $attributeGroupCodeRepository,
        AttributeSetCodeRepository $attributeSetCodeRepository,
        AttributeGroupRepositoryInterface $attributeGroupRepository,
        AttributeSetManagementInterface $attributeSetManagement,
        AttributeSetInterfaceFactory $attributeSetFactory,
        AttributeGroupInterfaceFactory $attributeGroupFactory,
        AttributeManagementInterface $attributeManagement,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        AttributeRepositoryInterface  $attributeRepository,
        ResourceConnection $resourceConnection,
        EntityTypeCodeRepository $entityTypeCodeRepository
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
        $this->resourceConnection = $resourceConnection;
        $this->entityTypeCodeRepository = $entityTypeCodeRepository;
    }

    public function save(AttributeSetInterface $attributeSet)
    {
        $connection  = $this->resourceConnection->getConnection();
        $connection->beginTransaction();
        try {
            $attributeSetCode = $attributeSet->getAttributeSetCode();
            $entityTypeId = $this->entityTypeCodeRepository->getEntityTypeId($attributeSet->getEntityTypeCode()); // todo: really get entity type id
            $attributeSetId = $this->attributeSetCodeRepository->getAttributeSetId($entityTypeId, $attributeSetCode);

            if (null === $attributeSetId) {
                $attributeSetId = $this->createAttributeSet($attributeSet, 11);
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
                /** @var AttributeGroupInterface $inputAttributeGroup */
                $inputAttributeGroup = $attributeGroupData['group'];
                if ($attributeGroupId === null) {
                    $attributeGroupId = $this->createAttributeGroup($attributeSetId, $inputAttributeGroup);
                } else {
                    $attributesInGroup = $this->getAttributes($entityTypeId, $attributeGroupId);

                    $this->removeAttributesFromGroup($attributeGroupId, array_diff($attributesInGroup, $inputAttributeGroup->getAttributes()));
                }

                $this->assignAttributesInGroup($inputAttributeGroup, $entityTypeId, $attributeSetId,
                    $attributeGroupId);
            }

            $connection->commit();
            return $attributeSet;
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    private function createAttributeGroup(int $attributeSetId, AttributeGroupInterface $attributeGroup): int
    {
        /** @var \Magento\Eav\Api\Data\AttributeGroupInterface $_attributeGroup */
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
    ) : int {
        $attributeSetCode = $attributeSet->getAttributeSetCode();
        $_attributeSet = $this->attributeSetFactory->create()
            ->setId(null)
            ->setEntityTypeId($attributeSet->getEntityTypeCode())
            ->setAttributeSetName($attributeSet->getName())
            ->setSortOrder($attributeSet->getSortOrder());

        $_attributeSet = $this->attributeSetManagement->create($_attributeSet, $skeletonId);
        $this->attributeSetCodeRepository->setAttributeSetCode($_attributeSet->getAttributeSetId(), $attributeSetCode);
        return $_attributeSet->getAttributeSetId();
    }

    private function assignAttributesInGroup(
        AttributeGroupInterface $inputAttributeGroup,
        int $entityTypeId,
        int $attributeSetId,
        int $attributeGroupId
    ) {
        $sortOrder = 0;
        foreach ($inputAttributeGroup->getAttributes() as $attributeCode) {
            $this->attributeManagement->assign(
                $entityTypeId,
                $attributeSetId,
                $attributeGroupId,
                $attributeCode,
                ++$sortOrder
            );
        }
    }

    private function getAttributes(int $groupId, int $entityTypeId)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(\Magento\Eav\Api\Data\AttributeGroupInterface::GROUP_ID, $groupId)
            ->create();

        return array_map(function (AttributeInterface $attribute) {
            return $attribute->getAttributeCode();
        },$this->attributeRepository->getList($entityTypeId, $searchCriteria)->getItems());
    }


    private function removeAttributesFromGroup(int $attributeSetId, array $attributesToRemove)
    {
        foreach ($attributesToRemove as $attributeToRemove) {
            $this->attributeManagement->unassign($attributeSetId, $attributeToRemove);
        }
    }
}