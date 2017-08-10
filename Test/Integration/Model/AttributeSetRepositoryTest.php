<?php
namespace SnowIO\AttributeSetCode\Test\Integration\Model;

use Magento\Eav\Api\AttributeGroupRepositoryInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Model\Entity\Attribute\Group;
use Magento\Eav\Model\Entity\Type;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\App\ObjectManager;
use SnowIO\AttributeSetCode\Api\AttributeSetRepositoryInterface as CodedAttributeSetRepository;
use SnowIO\AttributeSetCode\Api\Data\AttributeGroupInterface;
use SnowIO\AttributeSetCode\Api\Data\AttributeSetInterface;
use SnowIO\AttributeSetCode\Model\AttributeSetCodeRepository;
use SnowIO\AttributeSetCode\Model\EntityTypeCodeRepository;
use SnowIO\AttributeSetCode\Test\TestCase;

class AttributeSetRepositoryTest extends TestCase
{
    public function testCreateImplicitlyEmptyAttributeSet()
    {
        $attributeSetData = $this->createAttributeSet()
            ->setEntityTypeCode('catalog_product')
            ->setAttributeSetCode('my-test-attribute-set-1')
            ->setName('My Test Attribute Set 1')
            ->setSortOrder(50);

        $this->saveNewAttributeSetAndCheckDb($attributeSetData);
    }

    public function testCreateExplicitlyEmptyAttributeSet()
    {
        $attributeSetData = $this->createAttributeSet()
            ->setEntityTypeCode('catalog_product')
            ->setAttributeSetCode('my-test-attribute-set-1')
            ->setName('My Test Attribute Set 1')
            ->setSortOrder(50)
            ->setAttributeGroups([]);

        $this->saveNewAttributeSetAndCheckDb($attributeSetData);
    }

    public function testCreateAttributeSetWithImplicitlyEmptyAttributeGroups()
    {
        $attributeSetData = $this->createAttributeSet()
            ->setEntityTypeCode('catalog_product')
            ->setAttributeSetCode('my-test-attribute-set-1')
            ->setName('My Test Attribute Set 1')
            ->setSortOrder(50)
            ->setAttributeGroups([
                $this->createAttributeGroup()
                    ->setAttributeGroupCode('my-test-attribute-group-1')
                    ->setName('My Test Attribute Group 1'),
                $this->createAttributeGroup()
                    ->setAttributeGroupCode('my-test-attribute-group-2')
                    ->setName('My Test Attribute Group 2')
            ]);

        $this->saveNewAttributeSetAndCheckDb($attributeSetData);
    }

    public function testCreateAttributeSetWithExplicitlyEmptyAttributeGroups()
    {
        $attributeSetData = $this->createAttributeSet()
            ->setEntityTypeCode('catalog_product')
            ->setAttributeSetCode('my-test-attribute-set-1')
            ->setName('My Test Attribute Set 1')
            ->setSortOrder(50)
            ->setAttributeGroups([
                $this->createAttributeGroup()
                    ->setAttributeGroupCode('my-test-attribute-group-1')
                    ->setName('My Test Attribute Group 1')
                    ->setAttributes([]),
                $this->createAttributeGroup()
                    ->setAttributeGroupCode('my-test-attribute-group-2')
                    ->setName('My Test Attribute Group 2')
                    ->setAttributes([])
            ]);

        $this->saveNewAttributeSetAndCheckDb($attributeSetData);
    }

    public function testDifferentEntityTypesCanUseSameAttributeSetCode()
    {
        $productAttributeSet = $this->createAttributeSet()
            ->setEntityTypeCode('catalog_product')
            ->setAttributeSetCode('my-test-attribute-set')
            ->setName('My Test Product Attribute Set');
        $this->saveNewAttributeSetAndCheckDb($productAttributeSet);

        $categoryAttributeSet = $this->createAttributeSet()
            ->setEntityTypeCode('catalog_category')
            ->setAttributeSetCode('my-test-attribute-set')
            ->setName('My Test Category Attribute Set');
        $this->saveNewAttributeSetAndCheckDb($categoryAttributeSet);

        self::assertAttributeSetCorrectInDb($productAttributeSet);
    }

    public function testCreateAttributeSetWithNonEmptyGroup()
    {
        $fullAttributeSetData = $this->createAttributeSet()
            ->setEntityTypeCode('catalog_product')
            ->setAttributeSetCode('my-test-attribute-set-1')
            ->setName('My Test Attribute Set 1')
            ->setSortOrder(50)
            ->setAttributeGroups([
                $this->createAttributeGroup()
                    ->setAttributeGroupCode('my-test-attribute-group-1')
                    ->setName('My Test Attribute Group 1')
                    ->setAttributes(['sku', 'color', 'cost']),
                $this->createAttributeGroup()
                    ->setAttributeGroupCode('my-test-attribute-group-2')
                    ->setName('My Test Attribute Group 2')
                    ->setAttributes([])
            ]);

        $this->saveNewAttributeSetAndCheckDb($fullAttributeSetData);

        $partialAttributeSetData1 = $this->createAttributeSet()
            ->setEntityTypeCode($fullAttributeSetData->getEntityTypeCode())
            ->setAttributeSetCode($fullAttributeSetData->getAttributeSetCode())
            ->setName('My Test Attribute Set 1 - renamed!');
        $this->saveAttributeSet($partialAttributeSetData1);

        $fullAttributeSetData->setName($partialAttributeSetData1->getName());
        self::assertAttributeSetCorrectInDb($fullAttributeSetData);

        $partialAttributeSetData2 = $this->createAttributeSet()
            ->setEntityTypeCode($fullAttributeSetData->getEntityTypeCode())
            ->setAttributeSetCode($fullAttributeSetData->getAttributeSetCode())
            ->setAttributeGroups([
                $this->createAttributeGroup()
                    ->setAttributeGroupCode('my-test-attribute-group-1')
                    ->setSortOrder(5)
                    ->setAttributes(['sku', 'color']),
                $this->createAttributeGroup()
                    ->setAttributeGroupCode('my-test-attribute-group-2')
                    ->setName('My Test Attribute Group 2 - renamed!')
                    ->setAttributes(['cost'])
            ]);
        $this->saveAttributeSet($partialAttributeSetData2);

        $fullAttributeSetData->setAttributeGroups($partialAttributeSetData2->getAttributeGroups());
        $fullAttributeSetData->getAttributeGroups()[0]->setName('My Test Attribute Group 1');
        self::assertAttributeSetCorrectInDb($fullAttributeSetData);

        $partialAttributeSetData3 = $this->createAttributeSet()
            ->setEntityTypeCode($fullAttributeSetData->getEntityTypeCode())
            ->setAttributeSetCode($fullAttributeSetData->getAttributeSetCode())
            ->setAttributeGroups([
                $this->createAttributeGroup()
                    ->setAttributeGroupCode('my-test-attribute-group-1')
                    ->setSortOrder(5)
                    ->setAttributes(['sku', 'color'])
            ]);
        $this->saveAttributeSet($partialAttributeSetData3);

        $fullAttributeSetData->setAttributeGroups($partialAttributeSetData3->getAttributeGroups());
        $fullAttributeSetData->getAttributeGroups()[0]->setName('My Test Attribute Group 1');
        self::assertAttributeSetCorrectInDb($fullAttributeSetData);

        $partialAttributeSetData4 = $this->createAttributeSet()
            ->setEntityTypeCode($fullAttributeSetData->getEntityTypeCode())
            ->setAttributeSetCode($fullAttributeSetData->getAttributeSetCode())
            ->setAttributeGroups([
                $this->createAttributeGroup()
                    ->setAttributeGroupCode('my-test-attribute-group-1')
                    ->setName('My Test Attribute Group 1 - renamed')
            ]);
        $this->saveAttributeSet($partialAttributeSetData4);

        $fullAttributeSetData->setAttributeGroups([
            $this->createAttributeGroup()
                ->setAttributeGroupCode('my-test-attribute-group-1')
                ->setName('My Test Attribute Group 1 - renamed')
                ->setSortOrder(5)
                ->setAttributes(['sku', 'color']),
        ]);
        self::assertAttributeSetCorrectInDb($fullAttributeSetData);
    }

    private function createAttributeSet(): AttributeSetInterface
    {
        return ObjectManager::getInstance()->create(AttributeSetInterface::class);
    }

    private function createAttributeGroup(): AttributeGroupInterface
    {
        return ObjectManager::getInstance()->create(AttributeGroupInterface::class);
    }

    private function saveNewAttributeSetAndCheckDb(AttributeSetInterface $attributeSet)
    {
        self::removeAttributeSet($attributeSet);
        self::saveAttributeSet($attributeSet);
        self::assertAttributeSetCorrectInDb($attributeSet);
    }

    private function saveAttributeSet(AttributeSetInterface $attributeSet)
    {
        $objectManager = ObjectManager::getInstance();
        /** @var CodedAttributeSetRepository $attributeSetRepository */
        $attributeSetRepository = $objectManager->get(CodedAttributeSetRepository::class);
        $attributeSetRepository->save($attributeSet);
    }

    private static function assertAttributeSetCorrectInDb(AttributeSetInterface $expected)
    {
        $objectManager = ObjectManager::getInstance();
        /** @var AttributeSetRepositoryInterface $attributeSetRepository */
        $attributeSetRepository = $objectManager->get(AttributeSetRepositoryInterface::class);
        /** @var AttributeSetCodeRepository $attributeSetCodeRepository */
        $attributeSetCodeRepository = $objectManager->get(AttributeSetCodeRepository::class);
        $entityTypeId = self::getEntityTypeId($expected->getEntityTypeCode());
        $attributeSetId = $attributeSetCodeRepository->getAttributeSetId($entityTypeId, $expected->getAttributeSetCode());
        self::assertNotNull($attributeSetId, \sprintf("The attribute set %s:%s does not exist.", $expected->getEntityTypeCode(), $expected->getAttributeSetCode()));
        $actual = $attributeSetRepository->get($attributeSetId);

        self::assertAttributeSetAsExpected($expected, $actual);
    }

    private static function assertAttributeSetAsExpected(AttributeSetInterface $expected, \Magento\Eav\Api\Data\AttributeSetInterface $actual)
    {
        $expectedEntityTypeId = self::getEntityTypeId($expected->getEntityTypeCode());
        self::assertEquals($expectedEntityTypeId, $actual->getEntityTypeId());
        self::assertSame($expected->getName(), $actual->getAttributeSetName());
        $expectedAttributeGroups = $expected->getAttributeGroups() ?? [];
        $expectedAttributeGroups = self::ignoreSystemAttributesFromExpectedGroups($expected->getEntityTypeCode(), $expectedAttributeGroups);
        $expectedAttributeGroups = self::addSystemAttributesToExpectedGroups($expected->getEntityTypeCode(), $expectedAttributeGroups);
        self::assertAttributeGroupsAsExpected($expected->getEntityTypeCode(), $expectedAttributeGroups, $actual->getAttributeSetId());
    }

    private static function ignoreSystemAttributesFromExpectedGroups(string $entityTypeCode, array $expectedGroups): array
    {
        /** @var EntityTypeCodeRepository $entityTypeCodeRepository */
        $defaultAttributeSetId = self::getDefaultAttributeSetId($entityTypeCode);
        /** @var AttributeGroupRepositoryInterface $attributeGroupRepository */
        $defaultAttributeSetGroups = self::getAttributeGroups($defaultAttributeSetId);
        /** @var Group $defaultAttributeSetGroup */
        $attributesInDefaultAttributeSet = self::getAttributesByAttributeSet($entityTypeCode, $defaultAttributeSetId);
        $systemAttributesInDefaultAttributeSet = \array_filter($attributesInDefaultAttributeSet,
            function (AttributeInterface $attribute) {
                return !$attribute->getIsUserDefined();
            });
        $systemAttributeCodesInDefaultAttributeSet = \array_map(function (AttributeInterface $attribute) {
            return $attribute->getAttributeCode();
        }, $systemAttributesInDefaultAttributeSet);
        /** @var AttributeGroupInterface $expectedGroup */
        foreach ($expectedGroups as $expectedGroup) {
            $expectedGroupAttributes = $expectedGroup->getAttributes();
            if ($expectedGroupAttributes === null) {
                continue;
            }
            $nonSystemAttributes = \array_diff($expectedGroupAttributes, $systemAttributeCodesInDefaultAttributeSet);
            $expectedGroup->setAttributes($nonSystemAttributes);
        }

        return $expectedGroups;
    }

    /**
     * @param AttributeGroupInterface[] $expectedGroups
     * @return AttributeGroupInterface[]
     */
    private static function addSystemAttributesToExpectedGroups(string $entityTypeCode, array $expectedGroups): array
    {
        $objectManager = ObjectManager::getInstance();
        /** @var EntityTypeCodeRepository $entityTypeCodeRepository */
        $entityTypeCodeRepository = $objectManager->get(EntityTypeCodeRepository::class);

        $expectedGroupCodes = \array_map(function (AttributeGroupInterface $group) {
            return $group->getAttributeGroupCode();
        }, $expectedGroups);
        $expectedGroups = \array_combine($expectedGroupCodes, $expectedGroups);

        $defaultAttributeSetId = $entityTypeCodeRepository->getDefaultAttributeSetId($entityTypeCode);
        $defaultAttributeGroups = self::getAttributeGroups($defaultAttributeSetId);
        foreach ($defaultAttributeGroups as $attributeGroup) {
            $attributes = self::getAttributesByGroup($entityTypeCode, $attributeGroup->getAttributeGroupId());
            $systemAttributes = \array_filter($attributes, function (AttributeInterface $attribute) {
                return !$attribute->getIsUserDefined();
            });
            if (empty($systemAttributes)) {
                continue;
            }
            $systemAttributeCodes = \array_map(function (AttributeInterface $attribute) {
                return $attribute->getAttributeCode();
            }, $systemAttributes);

            $attributeGroupCode = $attributeGroup->getAttributeGroupCode();
            //check if the group is in the expected attribute group
            //if it not add it and its attribute codes
            //if it is add its attribute codes that are not already in the group
            if (!isset($expectedGroups[$attributeGroupCode])) {
                $expectedGroups[$attributeGroupCode] = $objectManager->create(AttributeGroupInterface::class)
                    ->setAttributeGroupCode($attributeGroupCode)
                    ->setAttributeGroupSortOrder($attributeGroup->getSortOrder())
                    ->setName($attributeGroup->getAttributeGroupName())
                    ->setAttributes($systemAttributeCodes);
            } else {
                $attributeCodes = \array_merge($expectedGroups[$attributeGroupCode]->getAttributes(), $systemAttributeCodes);
                $expectedGroups[$attributeGroupCode]->setAttributes(\array_unique($attributeCodes));
            }
        }

        return $expectedGroups;
    }

    private static function assertAttributeGroupsAsExpected(string $entityTypeCode, array $expectedGroups, string $actualAttributeSetId)
    {
        $expectedGroupsByCode = [];
        foreach ($expectedGroups as $expectedGroup) {
            $expectedGroupsByCode[$expectedGroup->getAttributeGroupCode()] = $expectedGroup;
        }

        $actualGroupsByCode = self::getAttributeGroups($actualAttributeSetId);

        self::assertSameSize(
            $expectedGroupsByCode,
            $actualGroupsByCode,
            \sprintf('Attribute set should have %s groups but actually has %s groups.', \count($expectedGroups), \count($actualGroupsByCode))
        );

        foreach ($expectedGroupsByCode as $groupCode => $expectedGroup) {
            self::assertArrayHasKey($groupCode, $actualGroupsByCode, "Attribute set is missing group $groupCode.");
            self::assertAttributeGroupAsExpected($entityTypeCode, $expectedGroup, $actualGroupsByCode[$groupCode]);
        }
    }

    private static function assertAttributeGroupAsExpected(string $entityTypeCode, AttributeGroupInterface $expected, Group $actual)
    {
        self::assertSame($expected->getName(), $actual->getAttributeGroupName());
        self::assertSame($expected->getAttributeGroupCode(), $actual->getAttributeGroupCode());

        if ($expected->getSortOrder() !== null) {
            self::assertSame($expected->getSortOrder(), (int)$actual->getSortOrder());
        }

        $expectedAttributeCodes = $expected->getAttributes() ?? [];
        $actualAttributes = self::getAttributesByGroup($entityTypeCode, $actual->getAttributeGroupId());
        self::assertSameSize($expectedAttributeCodes, $actualAttributes);
        $actualAttributeCodes = \array_map(function (AttributeInterface $attribute) {
            return $attribute->getAttributeCode();
        }, $actualAttributes);
        // asort() here until sort orders are handled correctly for system attributes
        \asort($expectedAttributeCodes);
        \asort($actualAttributeCodes);
        self::assertSame(\array_values($expectedAttributeCodes), \array_values($actualAttributeCodes));
    }

    /**
     * @return Group[]
     */
    private static function getAttributeGroups(int $attributeSetId): array
    {
        $objectManager = ObjectManager::getInstance();
        /** @var AttributeGroupRepositoryInterface $attributeGroupRepository */
        $attributeGroupRepository = $objectManager->get(AttributeGroupRepositoryInterface::class);

        $searchCriteria = $objectManager->create(SearchCriteriaBuilder::class)
            ->addFilter('attribute_set_id', $attributeSetId)
            ->create();
        $actualGroups = $attributeGroupRepository->getList($searchCriteria)->getItems();
        $groupsByCode = [];
        /** @var Group $actualAttributeGroup */
        foreach ($actualGroups as $actualAttributeGroup) {
            $groupsByCode[$actualAttributeGroup->getAttributeGroupCode()] = $actualAttributeGroup;
        }

        return $groupsByCode;
    }

    /**
     * @return AttributeInterface[]
     */
    private static function getAttributesByGroup(string $entityTypeCode, int $attributeGroupId): array
    {
        $objectManager = ObjectManager::getInstance();
        /** @var AttributeRepositoryInterface $attributeRepository */
        $attributeRepository = $objectManager->get(AttributeRepositoryInterface::class);

        $searchCriteria = $objectManager->create(SearchCriteriaBuilder::class)
            ->addFilter('attribute_group_id', $attributeGroupId)
            ->addSortOrder((new SortOrder())->setField('sort_order')->setDirection(SortOrder::SORT_ASC))
            ->create();

        return $attributeRepository->getList($entityTypeCode, $searchCriteria)->getItems();
    }

    private static function getAttributesByAttributeSet(string $entityTypeCode, int $attributeSetId): array
    {
        $objectManager = ObjectManager::getInstance();
        /** @var AttributeRepositoryInterface $attributeRepository */
        $attributeRepository = $objectManager->get(AttributeRepositoryInterface::class);

        $searchCriteria = $objectManager->create(SearchCriteriaBuilder::class)
            ->addFilter('attribute_set_id', $attributeSetId)
            ->addSortOrder((new SortOrder())->setField('sort_order')->setDirection(SortOrder::SORT_ASC))
            ->create();

        return $attributeRepository->getList($entityTypeCode, $searchCriteria)->getItems();
    }

    private static function removeAttributeSet(AttributeSetInterface $attributeSet)
    {
        $objectManager = ObjectManager::getInstance();
        /** @var AttributeSetCodeRepository $attributeSetCodeRepository */
        $attributeSetCodeRepository = $objectManager->get(AttributeSetCodeRepository::class);
        /** @var AttributeSetRepositoryInterface $attributeSetRepository */
        $attributeSetRepository = $objectManager->get(AttributeSetRepositoryInterface::class);

        $entityTypeId = self::getEntityTypeId($attributeSet->getEntityTypeCode());
        $attributeSetId = $attributeSetCodeRepository->getAttributeSetId($entityTypeId, $attributeSet->getAttributeSetCode());
        if ($attributeSetId !== null) {
            $attributeSetRepository->deleteById($attributeSetId);
        }
    }

    private static function getEntityTypeId(string $entityTypeCode): int
    {
        $objectManager = ObjectManager::getInstance();
        /** @var Type $entityType */
        $entityType = $objectManager->create(Type::class)->loadByCode($entityTypeCode);
        return $entityType->getEntityTypeId();
    }

    private static function getDefaultAttributeSetId(string $entityTypeCode): int
    {
        $objectManager = ObjectManager::getInstance();
        /** @var EntityTypeCodeRepository $entityTypeCodeRepository */
        $entityTypeCodeRepository = $objectManager->get(EntityTypeCodeRepository::class);
        return $entityTypeCodeRepository->getDefaultAttributeSetId($entityTypeCode);
    }
}
