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
        self::assertAttributeGroupsAsExpected($expected->getEntityTypeCode(), $expectedAttributeGroups, $actual->getAttributeSetId());
    }

    private static function assertAttributeGroupsAsExpected(string $entityTypeCode, array $expectedGroups, string $actualAttributeSetId)
    {
        $objectManager = ObjectManager::getInstance();
        /** @var AttributeGroupRepositoryInterface $attributeGroupRepository */
        $attributeGroupRepository = $objectManager->get(AttributeGroupRepositoryInterface::class);

        $expectedGroupsByCode = [];
        foreach ($expectedGroups as $expectedGroup) {
            $expectedGroupsByCode[$expectedGroup->getAttributeGroupCode()] = $expectedGroup;
        }

        $searchCriteria = $objectManager->create(SearchCriteriaBuilder::class)
            ->addFilter('attribute_set_id', $actualAttributeSetId)
            ->create();
        $actualGroups = $attributeGroupRepository->getList($searchCriteria)->getItems();
        $actualGroupsByCode = [];
        /** @var Group $actualAttributeGroup */
        foreach ($actualGroups as $actualAttributeGroup) {
            $actualGroupsByCode[$actualAttributeGroup->getAttributeGroupCode()] = $actualAttributeGroup;
        }

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
        $objectManager = ObjectManager::getInstance();
        /** @var AttributeRepositoryInterface $attributeRepository */
        $attributeRepository = $objectManager->get(AttributeRepositoryInterface::class);

        self::assertSame($expected->getName(), $actual->getAttributeGroupName());
        self::assertSame($expected->getAttributeGroupCode(), $actual->getAttributeGroupCode());

        if ($expected->getSortOrder() !== null) {
            self::assertSame($expected->getSortOrder(), (int)$actual->getSortOrder());
        }

        $expectedAttributeCodes = $expected->getAttributes() ?? [];
        $searchCriteria = $objectManager->create(SearchCriteriaBuilder::class)
            ->addFilter('attribute_set_id', $actual->getAttributeSetId())
            ->addFilter('attribute_group_id', $actual->getAttributeGroupId())
            ->addSortOrder((new SortOrder())->setField('sort_order')->setDirection(SortOrder::SORT_ASC))
            ->create();
        $actualAttributes = $attributeRepository->getList($entityTypeCode, $searchCriteria)->getItems();
        self::assertSameSize($expectedAttributeCodes, $actualAttributes);
        $actualAttributeCodes = \array_map(function (AttributeInterface $attribute) {
            return $attribute->getAttributeCode();
        }, $actualAttributes);
        self::assertSame($expectedAttributeCodes, $actualAttributeCodes);
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
}
