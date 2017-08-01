<?php
namespace SnowIO\AttributeSetCode\Test\Integration\Model;

use Magento\Eav\Api\AttributeGroupRepositoryInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Model\Entity\Attribute\Group;
use Magento\Eav\Model\Entity\Type;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ObjectManager;
use SnowIO\AttributeSetCode\Api\CodedAttributeSetRepositoryInterface;
use SnowIO\AttributeSetCode\Api\Data\AttributeGroupInterface;
use SnowIO\AttributeSetCode\Api\Data\AttributeSetInterface;
use SnowIO\AttributeSetCode\Model\AttributeSetCodeRepository;

class CodedAttributeSetRepositoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateImplicitlyEmptyAttributeSet()
    {
        $attributeSet = $this->createAttributeSet()
            ->setEntityTypeCode('catalog_product')
            ->setAttributeSetCode('my-test-attribute-set-1')
            ->setName('My Test Attribute Set 1')
            ->setSortOrder(50);

        $this->saveNewAttributeSetAndCheckDb($attributeSet);
    }

    public function testCreateExplicitlyEmptyAttributeSet()
    {
        $attributeSet = $this->createAttributeSet()
            ->setEntityTypeCode('catalog_product')
            ->setAttributeSetCode('my-test-attribute-set-1')
            ->setName('My Test Attribute Set 1')
            ->setSortOrder(50)
            ->setAttributeGroups([]);

        $this->saveNewAttributeSetAndCheckDb($attributeSet);
    }

    public function testCreateAttributeSetWithImplicitlyEmptyAttributeGroups()
    {
        $attributeSet = $this->createAttributeSet()
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

        $this->saveNewAttributeSetAndCheckDb($attributeSet);
    }

    public function testCreateAttributeSetWithExplicitlyEmptyAttributeGroups()
    {
        $attributeSet = $this->createAttributeSet()
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

        $this->saveNewAttributeSetAndCheckDb($attributeSet);
    }

    public function testCreateAttributeSetWithNonEmptyGroup()
    {
        $fullAttributeSet = $this->createAttributeSet()
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

        $this->saveNewAttributeSetAndCheckDb($fullAttributeSet);

        $partialAttributeSet1 = $this->createAttributeSet()
            ->setEntityTypeCode($fullAttributeSet->getEntityTypeCode())
            ->setAttributeSetCode($fullAttributeSet->getAttributeSetCode())
            ->setName('My Test Attribute Set 1 - renamed!');
        $this->saveAttributeSet($partialAttributeSet1);

        $fullAttributeSet->setName($partialAttributeSet1->getName());
        self::assertAttributeSetCorrectInDb($fullAttributeSet);

        $partialAttributeSet2 = $this->createAttributeSet()
            ->setEntityTypeCode($fullAttributeSet->getEntityTypeCode())
            ->setAttributeSetCode($fullAttributeSet->getAttributeSetCode())
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
        $this->saveAttributeSet($partialAttributeSet2);

        $fullAttributeSet->setAttributeGroups($partialAttributeSet2->getAttributeGroups());
        self::assertAttributeSetCorrectInDb($fullAttributeSet);
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
        $this->saveNewAttributeSet($attributeSet);
        self::assertAttributeSetCorrectInDb($attributeSet);
    }

    private function saveAttributeSetAndCheckDb(AttributeSetInterface $attributeSet)
    {
        $this->saveAttributeSet($attributeSet);
        self::assertAttributeSetCorrectInDb($attributeSet);
    }

    private function saveNewAttributeSet(AttributeSetInterface $attributeSet)
    {
        self::removeAttributeSet($attributeSet);
        self::saveAttributeSet($attributeSet);
    }

    private function saveAttributeSet(AttributeSetInterface $attributeSet)
    {
        $objectManager = ObjectManager::getInstance();
        /** @var CodedAttributeSetRepositoryInterface $attributeSetRepository */
        $attributeSetRepository = $objectManager->get(CodedAttributeSetRepositoryInterface::class);
        $attributeSetRepository->save($attributeSet);
    }

    private static function assertAttributeSetCorrectInDb(AttributeSetInterface $expectedAttributeSet)
    {
        $objectManager = ObjectManager::getInstance();
        /** @var AttributeSetRepositoryInterface $attributeSetRepository */
        $attributeSetRepository = $objectManager->get(AttributeSetRepositoryInterface::class);
        /** @var AttributeSetCodeRepository $attributeSetCodeRepository */
        $attributeSetCodeRepository = $objectManager->get(AttributeSetCodeRepository::class);
        $expectedEntityTypeId = self::getEntityTypeId($expectedAttributeSet->getEntityTypeCode());
        $attributeSetId = $attributeSetCodeRepository->getAttributeSetId($expectedEntityTypeId, $expectedAttributeSet->getAttributeSetCode());
        self::assertNotNull($attributeSetId);
        $actualAttributeSet = $attributeSetRepository->get($attributeSetId);

        self::assertAttributeSetAsExpected($expectedAttributeSet, $actualAttributeSet);
    }

    private static function assertAttributeSetAsExpected(AttributeSetInterface $expected, \Magento\Eav\Api\Data\AttributeSetInterface $actual)
    {
        $expectedEntityTypeId = self::getEntityTypeId($expected->getEntityTypeCode());
        self::assertEquals($expectedEntityTypeId, $actual->getEntityTypeId());
        self::assertSame($expected->getName(), $actual->getAttributeSetName());
        $expectedAttributeGroups = $expected->getAttributeGroups() ?? [];
        self::assertAttributeGroupsAsExpected($expectedAttributeGroups, $actual->getAttributeSetId());
    }

    private static function assertAttributeGroupsAsExpected(array $expectedGroups, string $actualAttributeSetId)
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
            self::assertAttributeGroupAsExpected($expectedGroup, $actualGroupsByCode[$groupCode]);
        }
    }

    private static function assertAttributeGroupAsExpected(AttributeGroupInterface $expected, Group $actual)
    {
        $objectManager = ObjectManager::getInstance();
        /** @var AttributeRepositoryInterface $attributeGroupRepository */
        $attributeRepository = $objectManager->get(AttributeRepositoryInterface::class);

        self::assertSame($expected->getName(), $actual->getAttributeGroupName());
        self::assertSame($expected->getAttributeGroupCode(), $actual->getAttributeGroupCode());

        if ($expected->getSortOrder() !== null) {
            self::assertSame($expected->getSortOrder(), $actual->getSortOrder());
        }

        $expectedAttributeCodes = $expected->getAttributes() ?? [];
        $searchCriteria = $objectManager->create(SearchCriteriaBuilder::class)
            ->addFilter('attribute_set_id', $actual->getAttributeSetId())
            ->addFilter('attribute_group_id', $actual->getAttributeGroupId())
            ->create();
        $actualAttributes = $attributeRepository->getList($searchCriteria)->getItems();
        self::assertSameSize($expected->getAttributes(), $actualAttributes);
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
