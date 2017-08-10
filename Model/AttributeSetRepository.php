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
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\App\ResourceConnection;
use SnowIO\AttributeSetCode\Api\Data\AttributeGroupInterface;
use SnowIO\AttributeSetCode\Api\Data\AttributeSetInterface;
use SnowIO\AttributeSetCode\Api\AttributeSetRepositoryInterface as CodedAttributeSetRepositoryInterface;

class AttributeSetRepository implements CodedAttributeSetRepositoryInterface
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
    private $sortOrderBuilder;

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
        EntityTypeCodeRepository $entityTypeCodeRepository,
        SortOrderBuilder $sortOrderBuilder
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
        $this->sortOrderBuilder = $sortOrderBuilder;
    }

    public function save(AttributeSetInterface $attributeSet)
    {
        $connection  = $this->resourceConnection->getConnection();
        $connection->beginTransaction();
        try {
            $attributeSetCode = $attributeSet->getAttributeSetCode();
            $entityTypeId = $this->entityTypeCodeRepository->getEntityTypeId($entityTypeCode = $attributeSet->getEntityTypeCode());
            $attributeSetId = $this->attributeSetCodeRepository->getAttributeSetId($entityTypeId, $attributeSetCode);

            if (null === $attributeSetId) {
                $isNewAttributeSet = true;
                $attributeSetId = $this->createAttributeSet($attributeSet, $entityTypeId);
            } else {
                $isNewAttributeSet = false;
                $this->updateAttributeSet($attributeSet, $attributeSetId);
            }

            $inputAttributeGroups = $attributeSet->getAttributeGroups();
            if (isset($inputAttributeGroups)) {
                $inputAttributeGroupCodeToIdMap = [];
                $inputAttributeGroupIdsThatAlreadyExist = [];
                $inputAttributeGroups = $this->ignoreSystemAttributes($entityTypeCode, $inputAttributeGroups);
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
                $existingAttributeGroupIds = $this->attributeGroupCodeRepository->getAttributeGroupIds($attributeSetId);
                $attributeGroupIdsToRemove = array_diff($existingAttributeGroupIds, $inputAttributeGroupIdsThatAlreadyExist);

                foreach ($inputAttributeGroupCodeToIdMap as $attributeGroupCode => $attributeGroupData) {
                    $attributeGroupId = $attributeGroupData['id'];
                    /** @var AttributeGroupInterface $inputAttributeGroup */
                    $inputAttributeGroup = $attributeGroupData['group'];
                    if ($attributeGroupId === null) {
                        $attributeGroupId = $this->createAttributeGroup($attributeSetId, $inputAttributeGroup);
                    } else {
                        if (null !== $inputAttributes = $inputAttributeGroup->getAttributes()) {
                            $inputAttributeCodes = \array_map(function (\SnowIO\AttributeSetCode\Api\Data\AttributeInterface $attribute) {
                                return $attribute->getAttributeCode();
                            }, $inputAttributes);
                            $attributesAlreadyInGroup = $this->getAttributes($attributeGroupId, $attributeSet->getEntityTypeCode());
                            foreach ($attributesAlreadyInGroup as $attribute) {
                                if ($attribute->getIsUserDefined() && !\in_array($attribute->getAttributeCode(), $inputAttributeCodes)) {
                                    $this->attributeManagement->unassign($attributeSetId, $attribute->getAttributeCode());
                                }
                            }
                        }
                        $this->updateAttributeGroup($inputAttributeGroup, $attributeGroupId);
                    }

                    $this->assignAttributesInGroup($inputAttributeGroup, $attributeSet->getEntityTypeCode(),
                        $attributeSetId,
                        $attributeGroupId);
                }

                $this->removeNonSystemAttributesAndEmptyGroups($attributeSet->getEntityTypeCode(), $attributeSetId, $attributeGroupIdsToRemove);
            } elseif ($isNewAttributeSet) {
                $defaultAttributeGroupIds = $this->attributeGroupCodeRepository->getAttributeGroupIds($attributeSetId);
                $this->removeNonSystemAttributesAndEmptyGroups($attributeSet->getEntityTypeCode(), $attributeSetId, $defaultAttributeGroupIds);
            }

            $connection->commit();
            return $this->attributeSetRepository->get($attributeSetId);
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    private function createAttributeGroup(int $attributeSetId, AttributeGroupInterface $attributeGroup): int
    {
        /** @var \Magento\Eav\Api\Data\AttributeGroupInterface $_attributeGroup */
        $_attributeGroup = $this->attributeGroupFactory->create();
        if (($name = $attributeGroup->getName()) !== null) {
            $_attributeGroup->setAttributeGroupName($name);
        }

        if (($sortOrder = $attributeGroup->getSortOrder()) !== null) {
            $_attributeGroup->setSortOrder($sortOrder);
        }

        $_attributeGroup->setAttributeSetId($attributeSetId);

        $_attributeGroup = $this->attributeGroupRepository->save($_attributeGroup);
        return $_attributeGroup->getAttributeGroupId();
    }

    private function removeNonSystemAttributesAndEmptyGroups(string $entityTypeCode, int $attributeSetId, array $attributeGroupIdsToRemove)
    {
        foreach ($attributeGroupIdsToRemove as $attributeGroupId) {
            $attributesInGroup = $this->getAttributes($attributeGroupId, $entityTypeCode);
            $removeGroup = true;
            foreach ($attributesInGroup as $attribute) {
                if ($attribute->getIsUserDefined()) {
                    $this->attributeManagement->unassign($attributeSetId, $attribute->getAttributeCode());
                } else {  // system attribute
                    $removeGroup = false;
                }
            }
            if ($removeGroup) {
                $this->attributeGroupRepository->deleteById($attributeGroupId);
            }
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
        $_attributeSet = $this->attributeSetManagement->create($attributeSet->getEntityTypeCode(), $_attributeSet, $defaultAttributeSetId);
        $this->attributeSetCodeRepository->setAttributeSetCode($_attributeSet->getAttributeSetId(), $attributeSetCode);
        return $_attributeSet->getAttributeSetId();
    }

    private function assignAttributesInGroup(
        AttributeGroupInterface $inputAttributeGroup,
        string $entityTypeCode,
        int $attributeSetId,
        int $attributeGroupId
    ) {
        foreach ($inputAttributeGroup->getAttributes() ?? [] as $attribute) {
            $this->attributeManagement->assign(
                $entityTypeCode,
                $attributeSetId,
                $attributeGroupId,
                $attribute->getAttributeCode(),
                $attribute->getSortOrder()
            );
        }
    }

    /**
     * @return AttributeInterface[]
     */
    private function getAttributes(int $groupId, string $entityTypeCode): array
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(\Magento\Eav\Api\Data\AttributeGroupInterface::GROUP_ID, $groupId)
            ->create();
        return $this->attributeRepository->getList($entityTypeCode, $searchCriteria)->getItems();
    }

    private function updateAttributeSet(AttributeSetInterface $attributeSet, int $attributeSetId)
    {
        /** @var \Magento\Eav\Api\Data\AttributeSetInterface $_attributeSet */
        $_attributeSet = $this->attributeSetRepository->get($attributeSetId);

        if (($name = $attributeSet->getName()) !== null) {
            $_attributeSet->setAttributeSetName($name);
        }

        if (($sortOrder = $attributeSet->getSortOrder()) !== null) {
            $_attributeSet->setSortOrder($sortOrder);
        }

        $this->attributeSetRepository->save($_attributeSet);
    }

    private function updateAttributeGroup(AttributeGroupInterface $inputAttributeGroup, int $attributeGroupId)
    {
        $_attributeGroup = $this->attributeGroupRepository->get($attributeGroupId);

        if (($sortOrder = $inputAttributeGroup->getSortOrder()) !== null) {
            $_attributeGroup->setSortOrder($sortOrder);
        }

        if (($name = $inputAttributeGroup->getName()) !== null) {
            $_attributeGroup->setAttributeGroupName($name);
        }

        $this->attributeGroupRepository->save($_attributeGroup);
    }

    private function ignoreSystemAttributes(string $entityTypeCode, array $inputAttributeGroups): array
    {
        $defaultAttributeSetId = $this->entityTypeCodeRepository->getDefaultAttributeSetId($entityTypeCode);
        $attributesInDefaultAttributeSet = $this->getAttributesByAttributeSet($entityTypeCode, $defaultAttributeSetId);
        $systemAttributes = \array_filter($attributesInDefaultAttributeSet, function (AttributeInterface $attribute)  {
            return !$attribute->getIsUserDefined();
        });
        $systemAttributeCodes = \array_map(function (AttributeInterface $attribute) {
            return $attribute->getAttributeCode();
        }, $systemAttributes);
        /** @var AttributeGroupInterface  $attributeGroup */
        foreach ($inputAttributeGroups as $attributeGroup) {
            $attributes = $attributeGroup->getAttributes();
            if ($attributes === null) {
                continue;
            }
            $nonSystemAttributes = \array_filter($attributes, function (\SnowIO\AttributeSetCode\Api\Data\AttributeInterface $attribute) use ($systemAttributeCodes) {
                return !\in_array($attribute->getAttributeCode(), $systemAttributeCodes);
            });
            $attributeGroup->setAttributes($nonSystemAttributes);
        }
        return $inputAttributeGroups;
    }

    private function getAttributesByAttributeSet(string $entityTypeCode, int $attributeSetId)
    {
        $sortOrder = $this->sortOrderBuilder
            ->setField('sort_order')
            ->setDirection(SortOrder::SORT_ASC)
            ->create();
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('attribute_set_id', $attributeSetId)
            ->addSortOrder($sortOrder)
            ->create();
        return $this->attributeRepository->getList($entityTypeCode, $searchCriteria)->getItems();
    }
}
