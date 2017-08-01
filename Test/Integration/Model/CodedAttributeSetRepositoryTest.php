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
            ->setAttributeSetCode('my-test-attribute-set-1')
            ->setName('My Test Attribute Set 1')
            ->setSortOrder(50)
            ->setEntityTypeCode('catalog_product');

        $this->createAttributeSetAndCheckDb($attributeSet);
    }

    public function testCreateExplicitlyEmptyAttributeSet()
    {
        $attributeSet = $this->createAttributeSet()
            ->setAttributeSetCode('my-test-attribute-set-1')
            ->setName('My Test Attribute Set 1')
            ->setSortOrder(50)
            ->setEntityTypeCode('catalog_product')
            ->setAttributeGroups([]);

        $this->createAttributeSetAndCheckDb($attributeSet);
    }

    public function testCreateAttributeSetWithImplicitlyEmptyAttributeGroups()
    {
        $attributeSet = $this->createAttributeSet()
            ->setAttributeSetCode('my-test-attribute-set-1')
            ->setName('My Test Attribute Set 1')
            ->setSortOrder(50)
            ->setEntityTypeCode('catalog_product')
            ->setAttributeGroups([
                $this->createAttributeGroup()
                    ->setAttributeGroupCode('my-test-attribute-group-1')
                    ->setName('My Test Attribute Group 1'),
                $this->createAttributeGroup()
                    ->setAttributeGroupCode('my-test-attribute-group-2')
                    ->setName('My Test Attribute Group 2')
            ]);

        $this->createAttributeSetAndCheckDb($attributeSet);
    }

    public function testCreateAttributeSetWithExplicitlyEmptyAttributeGroups()
    {
        $attributeSet = $this->createAttributeSet()
            ->setAttributeSetCode('my-test-attribute-set-1')
            ->setName('My Test Attribute Set 1')
            ->setSortOrder(50)
            ->setEntityTypeCode('catalog_product')
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

        $this->createAttributeSetAndCheckDb($attributeSet);
    }

    public function testCreateAttributeSetWithNonEmptyGroup()
    {
        $attributeSet = $this->createAttributeSet()
            ->setAttributeSetCode('my-test-attribute-set-1')
            ->setName('My Test Attribute Set 1')
            ->setSortOrder(50)
            ->setEntityTypeCode('catalog_product')
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

        $this->createAttributeSetAndCheckDb($attributeSet);

        $attributeSet = $this->createAttributeSet()
            ->setAttributeSetCode('my-test-attribute-set-1')
            ->setName('My Test Attribute Set 1')
            ->setSortOrder(50)
            ->setEntityTypeCode('catalog_product')
            ->setAttributeGroups([
                $this->createAttributeGroup()
                    ->setAttributeGroupCode('my-test-attribute-group-1')
                    ->setName('My Test Attribute Group 1')
                    ->setAttributes(['sku', 'color']),
                $this->createAttributeGroup()
                    ->setAttributeGroupCode('my-test-attribute-group-2')
                    ->setName('My Test Attribute Group 2')
                    ->setAttributes(['cost'])
            ]);

        $this->saveAttributeSetAndCheckDb($attributeSet);
    }

    private function createAttributeSet(): AttributeSetInterface
    {
        return ObjectManager::getInstance()->create(AttributeSetInterface::class);
    }

    private function createAttributeGroup(): AttributeGroupInterface
    {
        return ObjectManager::getInstance()->create(AttributeGroupInterface::class);
    }

    private function createAttributeSetAndCheckDb(AttributeSetInterface $attributeSet)
    {
        self::removeAttributeSet($attributeSet);
        $this->saveAttributeSetAndCheckDb($attributeSet, $attributeSetIsNew = true);
    }

    private function saveAttributeSetAndCheckDb(AttributeSetInterface $attributeSet, bool $attributeSetIsNew = false)
    {
        $objectManager = ObjectManager::getInstance();
        /** @var CodedAttributeSetRepositoryInterface $attributeSetRepository */
        $attributeSetRepository = $objectManager->get(CodedAttributeSetRepositoryInterface::class);
        $attributeSetRepository->save($attributeSet);
        self::assertAttributeSetCorrectInDb($attributeSet, $attributeSetIsNew);
    }

    private static function assertAttributeSetCorrectInDb(AttributeSetInterface $expectedAttributeSet, bool $attributeSetIsNew)
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

        self::assertAttributeSetAsExpected($expectedAttributeSet, $actualAttributeSet, $attributeSetIsNew);
    }

    private static function assertAttributeSetAsExpected(
        AttributeSetInterface $expected,
        \Magento\Eav\Api\Data\AttributeSetInterface $actual,
        bool $attributeSetIsNew
    ) {
        $expectedEntityTypeId = self::getEntityTypeId($expected->getEntityTypeCode());
        self::assertEquals($expectedEntityTypeId, $actual->getEntityTypeId());

        if ($expected->getName() !== null) {
            self::assertSame($expected->getName(), $actual->getAttributeSetName());
        }

        $expectedAttributeGroups = $expected->getAttributeGroups();
        if ($expectedAttributeGroups === null) {
            if ($attributeSetIsNew) {
                $expectedAttributeGroups = [];
            } else {
                return;
            }
        }
        self::assertAttributeGroupsAsExpected($expectedAttributeGroups, $actual->getAttributeSetId(), $attributeSetIsNew);
    }

    private static function assertAttributeGroupsAsExpected(array $expectedGroups, string $actualAttributeSetId, bool $attributeSetIsNew)
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
            self::assertAttributeGroupAsExpected($expectedGroup, $actualGroupsByCode[$groupCode], $attributeSetIsNew);
        }
    }

    private static function assertAttributeGroupAsExpected(AttributeGroupInterface $expected, Group $actual, bool $attributeSetIsNew)
    {
        $objectManager = ObjectManager::getInstance();
        /** @var AttributeRepositoryInterface $attributeGroupRepository */
        $attributeRepository = $objectManager->get(AttributeRepositoryInterface::class);

        if ($expected->getName() !== null) {
            self::assertSame($expected->getName(), $actual->getAttributeGroupName());
        }

        self::assertSame($expected->getAttributeGroupCode(), $actual->getAttributeGroupCode());

        if ($expected->getSortOrder() !== null) {
            self::assertSame($expected->getSortOrder(), $actual->getSortOrder());
        }

        $expectedAttributeCodes = $expected->getAttributes();
        if ($expectedAttributeCodes === null) {
            if ($attributeSetIsNew) {
                $expectedAttributeCodes = [];
            } else {
                return;
            }
        }
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

    private static function getEntityTypeId(string $entityTypeCode): int
    {
        $objectManager = ObjectManager::getInstance();
        /** @var Type $entityType */
        $entityType = $objectManager->create(Type::class)->loadByCode($entityTypeCode);
        return $entityType->getEntityTypeId();
    }

    private static function removeAttributeSet(AttributeSetInterface $attributeSet)
    {

    }
}
