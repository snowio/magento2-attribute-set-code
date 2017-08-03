<?php

namespace SnowIO\AttributeSetCode\Model;

use Magento\Eav\Api\AttributeSetManagementInterface;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
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
    private $attributeSetRepository;

    public function __construct(
        AttributeGroupCodeRepository $attributeGroupCodeRepository,
        AttributeSetCodeRepository $attributeSetCodeRepository,
        AttributeGroupRepositoryInterface $attributeGroupRepository,
        AttributeSetManagementInterface $attributeSetManagement,
        AttributeSetInterfaceFactory $attributeSetFactory,
        AttributeGroupInterfaceFactory $attributeGroupFactory,
        AttributeManagementInterface $attributeManagement,
        AttributeSetRepositoryInterface $attributeSetRepository,
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
        $this->attributeSetRepository = $attributeSetRepository;
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
            $entityTypeId = $this->entityTypeCodeRepository->getEntityTypeId($attributeSet->getEntityTypeCode());
            $attributeSetId = $this->attributeSetCodeRepository->getAttributeSetId($entityTypeId, $attributeSetCode);

            if (null === $attributeSetId) {
                $attributeSetId = $this->createAttributeSet($attributeSet, $entityTypeId);
            } else {
                $this->updateAttributeSet($attributeSet, $attributeSetId, $entityTypeId);
            }

            $inputAttributeGroups = $attributeSet->getAttributeGroups();
            if (isset($inputAttributeGroups)) {
                $inputAttributeGroupCodeToIdMap = [];
                $inputAttributeGroupIdsThatAlreadyExist = [];
                foreach ($inputAttributeGroups as $inputAttributeGroup) {
                    $attributeGroupCode = $inputAttributeGroup->getAttributeGroupCode();
                    $attributeGroupId = $this->attributeGroupCodeRepository->getAttributeGroupId($attributeGroupCode,
                        $attributeSetId);
                    $inputAttributeGroupCodeToIdMap[$attributeGroupCode] = [
                        'id' => $attributeGroupId,
                        'group' => $inputAttributeGroup
                    ];

                    if ($attributeGroupId != null) {
                        $inputAttributeGroupIdsThatAlreadyExist[] = $attributeGroupId;
                    }

                }

                //input attribute group code is a map that contains attribute group code -> attribute group id
                $existingAttributeGroupIds = $this->attributeGroupCodeRepository->getAttributeGroupIds($attributeSetCode) ?? [];
                $attributeGroupIdsToRemove = array_diff($existingAttributeGroupIds, $inputAttributeGroupIdsThatAlreadyExist);
                $this->removeAttributeGroups($attributeGroupIdsToRemove);

                foreach ($inputAttributeGroupCodeToIdMap as $attributeGroupCode => $attributeGroupData) {
                    $attributeGroupId = $attributeGroupData['id'];
                    /** @var AttributeGroupInterface $inputAttributeGroup */
                    $inputAttributeGroup = $attributeGroupData['group'];
                    if ($attributeGroupId === null) {
                        $attributeGroupId = $this->createAttributeGroup($attributeSetId, $inputAttributeGroup);
                    } else {
                        $attributesInGroup = $this->getAttributes($attributeGroupId, $entityTypeId);
                        if ($attributesInGroup !== null) {
                            $this->removeAttributesFromSet($attributeSetId,
                                array_diff($attributesInGroup, $inputAttributeGroup->getAttributes()));
                        }
                    }

                    $this->assignAttributesInGroup($inputAttributeGroup, $attributeSet->getEntityTypeCode(),
                        $attributeSetId,
                        $attributeGroupId);
                }
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
        int $entityTypeId
    ) : int {
        $attributeSetCode = $attributeSet->getAttributeSetCode();
        $defaultAttributeSetId = $this->entityTypeCodeRepository->getDefaultAttributeSetId($attributeSet->getEntityTypeCode());
        $_attributeSet = $this->attributeSetFactory->create()
            ->setId(null)
            ->setEntityTypeId($entityTypeId)
            ->setAttributeSetName($attributeSet->getName())
            ->setSortOrder($attributeSet->getSortOrder());

        $_attributeSet = $this->attributeSetManagement->create($attributeSet->getEntityTypeCode(),$_attributeSet, $defaultAttributeSetId);
        $attributeGroupIdsToRemove = $this->attributeGroupCodeRepository->getAttributeGroupIds($_attributeSet->getAttributeSetId());
        foreach ($attributeGroupIdsToRemove as $attributeGroupIdToRemove) {
            $this->attributeGroupRepository->deleteById($attributeGroupIdToRemove);
        }
        $this->attributeSetCodeRepository->setAttributeSetCode($_attributeSet->getAttributeSetId(), $attributeSetCode);
        return $_attributeSet->getAttributeSetId();
    }




    private function assignAttributesInGroup(
        AttributeGroupInterface $inputAttributeGroup,
        string $entityTypeCode,
        int $attributeSetId,
        int $attributeGroupId
    ) {
        $sortOrder = 0;
        foreach ($inputAttributeGroup->getAttributes() ?? [] as $attributeCode) {
            $this->attributeManagement->assign($entityTypeCode, $attributeSetId, $attributeGroupId, $attributeCode, $sortOrder);
            $sortOrder++;
        }
    }

    private function getAttributes(int $groupId, int $entityTypeId)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(\Magento\Eav\Api\Data\AttributeGroupInterface::GROUP_ID, $groupId)
            ->create();
        $entityTypeCode = $this->entityTypeCodeRepository->getEntityTypeCode($entityTypeId);
        return array_map(function (AttributeInterface $attribute) {
            return $attribute->getAttributeCode();
        },$this->attributeRepository->getList($entityTypeCode, $searchCriteria)->getItems());
    }


    private function removeAttributesFromSet(int $attributeSetId, array $attributesToRemove)
    {
        foreach ($attributesToRemove as $attributeToRemove) {
            $this->attributeManagement->unassign($attributeSetId, $attributeToRemove);
        }
    }

    private function updateAttributeSet(AttributeSetInterface $attributeSet, int $attributeSetId, int $entityTypeId)
    {
        $_attributeSet = $this->attributeSetFactory->create()
            ->setId($attributeSetId)
            ->setEntityTypeId($entityTypeId)
            ->setAttributeSetName($attributeSet->getName())
            ->setSortOrder($attributeSet->getSortOrder());
        $this->attributeSetRepository->save($_attributeSet);
    }
}