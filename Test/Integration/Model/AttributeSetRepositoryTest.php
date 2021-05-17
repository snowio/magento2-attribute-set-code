<?php
namespace SnowIO\AttributeSetCode\Test\Integration\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\ConfigurableProduct\Api\Data\OptionInterface;
use Magento\ConfigurableProduct\Api\Data\OptionInterfaceFactory;
use Magento\ConfigurableProduct\Api\Data\OptionValueInterface;
use Magento\ConfigurableProduct\Api\Data\OptionValueInterfaceFactory;
use Magento\Eav\Api\AttributeGroupRepositoryInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Api\Data\AttributeInterfaceFactory;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection as AttributeCollection;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Framework\Exception\StateException;
use SnowIO\AttributeSetCode\Api\Data\AttributeInterface as SnowIOAttributeInterface;
use Magento\Eav\Model\Entity\Attribute\Group;
use Magento\Eav\Model\Entity\Type;
use Magento\Framework\Api\SearchCriteriaBuilder;
use SnowIO\AttributeSetCode\Api\AttributeSetRepositoryInterface as CodedAttributeSetRepository;
use SnowIO\AttributeSetCode\Api\Data\AttributeGroupInterface;
use SnowIO\AttributeSetCode\Api\Data\AttributeSetInterface;
use SnowIO\AttributeSetCode\Model\Attribute;
use SnowIO\AttributeSetCode\Model\AttributeGroup;
use SnowIO\AttributeSetCode\Model\AttributeSetCodeRepository;
use SnowIO\AttributeSetCode\Model\EntityTypeCodeRepository;
use SnowIO\AttributeSetCode\Model\AttributeSet;

class AttributeSetRepositoryTest extends \PHPUnit\Framework\TestCase
{
    private $objectManager;
    
    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->objectManager = Bootstrap::getObjectManager();
    }

    public function testCreateAttributeSetWithImplicitlyEmptyAttributeGroups()
    {
        $attributeSetData = $this->createAttributeSet()
            ->setEntityTypeCode('catalog_product')
            ->setAttributeSetCode('test-attribute-set-1')
            ->setName('My Test Attribute Set 1')
            ->setSortOrder(50)
            ->setAttributeGroups([
                $this->createAttributeGroup()
                    ->setAttributeGroupCode('test-attribute-group-1')
                    ->setName('My Test Attribute Group 1'),
                $this->createAttributeGroup()
                    ->setAttributeGroupCode('test-attribute-group-2')
                    ->setName('My Test Attribute Group 2')
            ]);

        $this->saveNewAttributeSetAndCheckDb($attributeSetData);
    }

    public function testCreateAttributeSetWithExplicitlyEmptyAttributeGroups()
    {
        $attributeSetData = $this->createAttributeSet()
            ->setEntityTypeCode('catalog_product')
            ->setAttributeSetCode('test-attribute-set-1')
            ->setName('My Test Attribute Set 1')
            ->setSortOrder(50)
            ->setAttributeGroups([
                $this->createAttributeGroup()
                    ->setAttributeGroupCode('test-attribute-group-1')
                    ->setName('My Test Attribute Group 1')
                    ->setAttributes([]),
                $this->createAttributeGroup()
                    ->setAttributeGroupCode('test-attribute-group-2')
                    ->setName('My Test Attribute Group 2')
                    ->setAttributes([])
            ]);

        $this->saveNewAttributeSetAndCheckDb($attributeSetData);
    }

    public function testDifferentEntityTypesCanUseSameAttributeSetCode()
    {
        $productAttributeSet = $this->createAttributeSet()
            ->setEntityTypeCode('catalog_product')
            ->setAttributeSetCode('test-attribute-set')
            ->setName('My Test Product Attribute Set');
        $this->saveNewAttributeSetAndCheckDb($productAttributeSet);

        $categoryAttributeSet = $this->createAttributeSet()
            ->setEntityTypeCode('catalog_category')
            ->setAttributeSetCode('test-attribute-set')
            ->setName('My Test Category Attribute Set');
        $this->saveNewAttributeSetAndCheckDb($categoryAttributeSet);

        self::assertAttributeSetCorrectInDb($productAttributeSet);
    }

    public function testChangeGroupCode()
    {
        $fullAttributeSetData1 = $this->createAttributeSet()
            ->setEntityTypeCode('catalog_product')
            ->setAttributeSetCode('test-attribute-set-1')
            ->setName('My Test Attribute Set 1')
            ->setSortOrder(50)
            ->setAttributeGroups([
                $this->createAttributeGroup()
                    ->setAttributeGroupCode('test-attribute-group-1')
                    ->setName('My Test Attribute Group 1')
                    ->setAttributes([
                        $this->createAttribute()->setAttributeCode('sku')->setSortOrder(20),
                        $this->createAttribute()->setAttributeCode('color')->setSortOrder(100),
                        $this->createAttribute()->setAttributeCode('cost')->setSortOrder(6)
                    ]),
                $this->createAttributeGroup()
                    ->setAttributeGroupCode('test-attribute-group-2')
                    ->setName('My Test Attribute Group 2')
                    ->setAttributes([])
            ]);
        $this->saveNewAttributeSetAndCheckDb($fullAttributeSetData1);

        $fullAttributeSetData2 = $this->createAttributeSet()
            ->setEntityTypeCode('catalog_product')
            ->setAttributeSetCode('test-attribute-set-1')
            ->setName('My Test Attribute Set 1')
            ->setSortOrder(50)
            ->setAttributeGroups([
                $this->createAttributeGroup()
                    ->setAttributeGroupCode('test-attribute-group-1')
                    ->setName('My Test Attribute Group 1')
                    ->setAttributes([
                        $this->createAttribute()->setAttributeCode('sku')->setSortOrder(20),
                        $this->createAttribute()->setAttributeCode('color')->setSortOrder(100),
                        $this->createAttribute()->setAttributeCode('cost')->setSortOrder(6)
                    ]),
                $this->createAttributeGroup()
                    ->setAttributeGroupCode('my-test-attribute-group-2')
                    ->setName('My Test Attribute Group 2')
                    ->setAttributes([])
            ]);
        $this->saveAttributeSet($fullAttributeSetData2);

        self::assertAttributeSetCorrectInDb($fullAttributeSetData2);
    }

    public function testCreateAttributeSetWithNonEmptyGroup()
    {
        $fullAttributeSetData = $this->createAttributeSet()
            ->setEntityTypeCode('catalog_product')
            ->setAttributeSetCode('test-attribute-set-1')
            ->setName('My Test Attribute Set 1')
            ->setSortOrder(50)
            ->setAttributeGroups([
                $this->createAttributeGroup()
                    ->setAttributeGroupCode('test-attribute-group-1')
                    ->setName('My Test Attribute Group 1')
                    ->setAttributes([
                        $this->createAttribute()->setAttributeCode('sku')->setSortOrder(20),
                        $this->createAttribute()->setAttributeCode('color')->setSortOrder(100),
                        $this->createAttribute()->setAttributeCode('cost')->setSortOrder(6)
                    ]),
                $this->createAttributeGroup()
                    ->setAttributeGroupCode('test-attribute-group-2')
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
                    ->setAttributeGroupCode('test-attribute-group-1')
                    ->setSortOrder(5)
                    ->setAttributes([
                        $this->createAttribute()->setAttributeCode('sku')->setSortOrder(20),
                        $this->createAttribute()->setAttributeCode('color')->setSortOrder(100)
                    ]),
                $this->createAttributeGroup()
                    ->setAttributeGroupCode('test-attribute-group-2')
                    ->setName('My Test Attribute Group 2 - renamed!')
                    ->setAttributes([
                        $this->createAttribute()->setAttributeCode('cost')->setSortOrder(6)
                    ])
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
                    ->setAttributeGroupCode('test-attribute-group-1')
                    ->setSortOrder(5)
                    ->setAttributes([
                        $this->createAttribute()->setAttributeCode('sku')->setSortOrder(20),
                        $this->createAttribute()->setAttributeCode('color')->setSortOrder(100)
                    ])
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
                    ->setAttributeGroupCode('test-attribute-group-1')
                    ->setName('My Test Attribute Group 1 - renamed')
            ]);
        $this->saveAttributeSet($partialAttributeSetData4);

        $fullAttributeSetData->setAttributeGroups([
            $this->createAttributeGroup()
                ->setAttributeGroupCode('test-attribute-group-1')
                ->setName('My Test Attribute Group 1 - renamed')
                ->setSortOrder(5)
                ->setAttributes([
                    $this->createAttribute()->setAttributeCode('sku')->setSortOrder(20),
                    $this->createAttribute()->setAttributeCode('color')->setSortOrder(100)]),
        ]);
        self::assertAttributeSetCorrectInDb($fullAttributeSetData);
    }

    public function testRecreateAttributeGroupWithAssociatedConfigurableProduct()
    {
        $fullAttributeSetData = $this->createAttributeSet()
            ->setEntityTypeCode('catalog_product')
            ->setAttributeSetCode('configurable-product-attribute-set')
            ->setName('My Test Attribute Set 1')
            ->setSortOrder(50)
            ->setAttributeGroups([
                $this->createAttributeGroup()
                    ->setAttributeGroupCode('test-attribute-group-1')
                    ->setName('My Test Attribute Group 1')
                    ->setAttributes([
                        $this->createAttribute()->setAttributeCode('sku')->setSortOrder(20),
                        $this->createAttribute()->setAttributeCode('color')->setSortOrder(100),
                        $this->createAttribute()->setAttributeCode('cost')->setSortOrder(6),
                    ]),
            ]);

        $this->saveNewAttributeSetAndCheckDb($fullAttributeSetData);

        $configurableProduct = self::getConfigurableProductData(__METHOD__, ['color']);
        $extensionAttributes = $configurableProduct->getExtensionAttributes();
        $extensionAttributes->setAttributeSetCode($fullAttributeSetData->getAttributeSetCode());
        $configurableProduct->setExtensionAttributes($extensionAttributes);
        self::saveNewProduct($configurableProduct);

        $fullAttributeSetData2 = $this->createAttributeSet()
            ->setEntityTypeCode('catalog_product')
            ->setAttributeSetCode('configurable-product-attribute-set')
            ->setName('My Test Attribute Set 1')
            ->setSortOrder(50)
            ->setAttributeGroups([
                $this->createAttributeGroup()
                    ->setAttributeGroupCode('test-attribute-group-2')
                    ->setName('My Test Attribute Group 2')
                    ->setAttributes([
                        $this->createAttribute()->setAttributeCode('sku')->setSortOrder(20),
                        $this->createAttribute()->setAttributeCode('color')->setSortOrder(100),
                    ]),
            ]);

        $this->saveAttributeSet($fullAttributeSetData2);
        self::assertAttributeSetCorrectInDb($fullAttributeSetData2);
    }

    public function testMoveAttributesToNewGroupWithAssociatedConfigurableProduct()
    {
        self::saveNewSizeAttribute();

        $fullAttributeSetData = $this->createAttributeSet()
            ->setEntityTypeCode('catalog_product')
            ->setAttributeSetCode('configurable-product-attribute-set')
            ->setName('My Test Attribute Set 1')
            ->setSortOrder(50)
            ->setAttributeGroups([
                $this->createAttributeGroup()
                    ->setAttributeGroupCode('test-attribute-group-1')
                    ->setName('My Test Attribute Group 1')
                    ->setAttributes([
                        $this->createAttribute()->setAttributeCode('sku')->setSortOrder(20),
                        $this->createAttribute()->setAttributeCode('color')->setSortOrder(100),
                    ]),
                $this->createAttributeGroup()
                    ->setAttributeGroupCode('test-attribute-group-2')
                    ->setName('My Test Attribute Group 2')
                    ->setAttributes([
                        $this->createAttribute()->setAttributeCode('size')->setSortOrder(100),
                    ]),
            ]);

        $this->saveNewAttributeSetAndCheckDb($fullAttributeSetData);

        $configurableProduct = self::getConfigurableProductData(__METHOD__, ['color', 'size']);
        $extensionAttributes = $configurableProduct->getExtensionAttributes();
        $extensionAttributes->setAttributeSetCode($fullAttributeSetData->getAttributeSetCode());
        $configurableProduct->setExtensionAttributes($extensionAttributes);
        self::saveNewProduct($configurableProduct);

        $fullAttributeSetData2 = $this->createAttributeSet()
            ->setEntityTypeCode('catalog_product')
            ->setAttributeSetCode('configurable-product-attribute-set')
            ->setName('My Test Attribute Set 1')
            ->setSortOrder(50)
            ->setAttributeGroups([
                $this->createAttributeGroup()
                    ->setAttributeGroupCode('test-attribute-group-1')
                    ->setName('My Test Attribute Group 1')
                    ->setAttributes([
                        $this->createAttribute()->setAttributeCode('sku')->setSortOrder(20),
                        $this->createAttribute()->setAttributeCode('size')->setSortOrder(100),
                    ]),
                $this->createAttributeGroup()
                    ->setAttributeGroupCode('test-attribute-group-2')
                    ->setName('My Test Attribute Group 2')
                    ->setAttributes([
                        $this->createAttribute()->setAttributeCode('color')->setSortOrder(100),
                    ]),
            ]);

        $this->saveAttributeSet($fullAttributeSetData2);
        self::assertAttributeSetCorrectInDb($fullAttributeSetData2);
    }

    public function testConfigurableAttributesArePreserved()
    {
        $fullAttributeSetData = $this->createAttributeSet()
            ->setEntityTypeCode('catalog_product')
            ->setAttributeSetCode('configurable-product-attribute-set')
            ->setName('My Test Attribute Set 1')
            ->setSortOrder(50)
            ->setAttributeGroups([
                $this->createAttributeGroup()
                    ->setAttributeGroupCode('test-attribute-group-1')
                    ->setName('My Test Attribute Group 1')
                    ->setAttributes([
                        $this->createAttribute()->setAttributeCode('sku')->setSortOrder(20),
                        $this->createAttribute()->setAttributeCode('color')->setSortOrder(100),
                        $this->createAttribute()->setAttributeCode('cost')->setSortOrder(6),
                    ]),
            ]);

        $this->saveNewAttributeSetAndCheckDb($fullAttributeSetData);

        $configurableProduct = self::getConfigurableProductData(__METHOD__, ['color']);
        $extensionAttributes = $configurableProduct->getExtensionAttributes();
        $extensionAttributes->setAttributeSetCode($fullAttributeSetData->getAttributeSetCode());
        $configurableProduct->setExtensionAttributes($extensionAttributes);
        self::saveNewProduct($configurableProduct);

        $fullAttributeSetData2 = $this->createAttributeSet()
            ->setEntityTypeCode('catalog_product')
            ->setAttributeSetCode('configurable-product-attribute-set')
            ->setName('My Test Attribute Set 1')
            ->setSortOrder(50)
            ->setAttributeGroups([
                $this->createAttributeGroup()
                    ->setAttributeGroupCode('test-attribute-group-1')
                    ->setName('My Test Attribute Group 1')
                    ->setAttributes([
                        $this->createAttribute()->setAttributeCode('sku')->setSortOrder(20),
                    ]),
            ]);

        $this->saveAttributeSet($fullAttributeSetData2);

        $expectedResult = $this->createAttributeSet()
            ->setEntityTypeCode('catalog_product')
            ->setAttributeSetCode('configurable-product-attribute-set')
            ->setName('My Test Attribute Set 1')
            ->setSortOrder(50)
            ->setAttributeGroups([
                $this->createAttributeGroup()
                    ->setAttributeGroupCode('test-attribute-group-1')
                    ->setName('My Test Attribute Group 1')
                    ->setAttributes([
                        $this->createAttribute()->setAttributeCode('sku')->setSortOrder(20),
                        $this->createAttribute()->setAttributeCode('color')->setSortOrder(100),
                    ]),
            ]);

        self::assertAttributeSetCorrectInDb($expectedResult);
    }

    public function testDeletedGroupsContainingConfigurableAttributesArePreserved()
    {
        $fullAttributeSetData = $this->createAttributeSet()
            ->setEntityTypeCode('catalog_product')
            ->setAttributeSetCode('configurable-product-attribute-set')
            ->setName('My Test Attribute Set 1')
            ->setSortOrder(50)
            ->setAttributeGroups([
                $this->createAttributeGroup()
                    ->setAttributeGroupCode('test-attribute-group-1')
                    ->setName('My Test Attribute Group 1')
                    ->setAttributes([
                        $this->createAttribute()->setAttributeCode('sku')->setSortOrder(20),
                    ]),
                $this->createAttributeGroup()
                    ->setAttributeGroupCode('test-attribute-group-2')
                    ->setName('My Test Attribute Group 2')
                    ->setAttributes([
                        $this->createAttribute()->setAttributeCode('color')->setSortOrder(100),
                        $this->createAttribute()->setAttributeCode('cost')->setSortOrder(6),
                    ]),
            ]);

        $this->saveNewAttributeSetAndCheckDb($fullAttributeSetData);

        $configurableProduct = self::getConfigurableProductData(__METHOD__, ['color']);
        $extensionAttributes = $configurableProduct->getExtensionAttributes();
        $extensionAttributes->setAttributeSetCode($fullAttributeSetData->getAttributeSetCode());
        $configurableProduct->setExtensionAttributes($extensionAttributes);
        self::saveNewProduct($configurableProduct);

        $fullAttributeSetData2 = $this->createAttributeSet()
            ->setEntityTypeCode('catalog_product')
            ->setAttributeSetCode('configurable-product-attribute-set')
            ->setName('My Test Attribute Set 1')
            ->setSortOrder(50)
            ->setAttributeGroups([
                $this->createAttributeGroup()
                    ->setAttributeGroupCode('test-attribute-group-1')
                    ->setName('My Test Attribute Group 1')
                    ->setAttributes([
                        $this->createAttribute()->setAttributeCode('sku')->setSortOrder(20),
                    ]),
            ]);

        $this->saveAttributeSet($fullAttributeSetData2);

        $expectedResult = $this->createAttributeSet()
            ->setEntityTypeCode('catalog_product')
            ->setAttributeSetCode('configurable-product-attribute-set')
            ->setName('My Test Attribute Set 1')
            ->setSortOrder(50)
            ->setAttributeGroups([
                $this->createAttributeGroup()
                    ->setAttributeGroupCode('test-attribute-group-1')
                    ->setName('My Test Attribute Group 1')
                    ->setAttributes([
                        $this->createAttribute()->setAttributeCode('sku')->setSortOrder(20),
                    ]),
                $this->createAttributeGroup()
                    ->setAttributeGroupCode('test-attribute-group-2')
                    ->setName('My Test Attribute Group 2')
                    ->setAttributes([
                        $this->createAttribute()->setAttributeCode('color')->setSortOrder(100),
                    ]),
            ]);

        self::assertAttributeSetCorrectInDb($expectedResult);
    }

    private function createAttributeSet(): AttributeSet
    {
        return $this->objectManager->create(AttributeSet::class);
    }

    private function createAttributeGroup(): AttributeGroupInterface
    {
        return $this->objectManager->create(AttributeGroup::class);
    }

    private function createAttribute(): SnowIOAttributeInterface
    {
        return $this->objectManager->create(Attribute::class);
    }

    private function saveNewAttributeSetAndCheckDb(AttributeSetInterface $attributeSet)
    {
        self::removeAttributeSet($attributeSet);
        self::saveAttributeSet($attributeSet);
        self::assertAttributeSetCorrectInDb($attributeSet);
    }

    private function saveAttributeSet(AttributeSetInterface $attributeSet)
    {
        $objectManager = Bootstrap::getObjectManager();
        /** @var CodedAttributeSetRepository $attributeSetRepository */
        $attributeSetRepository = $objectManager->get(CodedAttributeSetRepository::class);
        self::assertNull($attributeSetRepository->save($attributeSet));
    }

    private static function assertAttributeSetCorrectInDb(AttributeSetInterface $expected)
    {
        $objectManager = Bootstrap::getObjectManager();
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
        self::assertAttributeGroupsAsExpected($expectedAttributeGroups, $actual->getAttributeSetId());
    }

    private static function ignoreSystemAttributesFromExpectedGroups(string $entityTypeCode, array $expectedGroups): array
    {
        $defaultAttributeSetId = self::getDefaultAttributeSetId($entityTypeCode);
        $systemAttributesInDefaultAttributeSet = [];
        foreach (self::getAttributeGroups($defaultAttributeSetId) as $actualGroup) {
            $attributeGroupId = $actualGroup->getAttributeGroupId();
            $attributes = self::getAttributesByGroup($attributeGroupId);
            foreach ($attributes as $attribute) {
                if (!$attribute->getIsUserDefined()) {
                    $systemAttributesInDefaultAttributeSet[] = self::convertEavAttribute($attribute, $attributeGroupId);
                }
            }
        }
        /** @var AttributeGroupInterface $expectedGroup */
        foreach ($expectedGroups as $expectedGroup) {
            $expectedGroupAttributes = $expectedGroup->getAttributes();
            if ($expectedGroupAttributes === null) {
                continue;
            }
            $nonSystemAttributes = \array_udiff($expectedGroupAttributes, $systemAttributesInDefaultAttributeSet,
                function (SnowIOAttributeInterface $a, SnowIOAttributeInterface $b) {
                    return strcmp($a->getAttributeCode(), $b->getAttributeCode());
                }
            );
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
        $objectManager = Bootstrap::getObjectManager();
        /** @var EntityTypeCodeRepository $entityTypeCodeRepository */
        $entityTypeCodeRepository = $objectManager->get(EntityTypeCodeRepository::class);

        $expectedGroupCodes = \array_map(function (AttributeGroupInterface $group) {
            return $group->getAttributeGroupCode();
        }, $expectedGroups);
        $expectedGroups = \array_combine($expectedGroupCodes, $expectedGroups);

        $defaultAttributeSetId = $entityTypeCodeRepository->getDefaultAttributeSetId($entityTypeCode);
        $defaultAttributeGroups = self::getAttributeGroups($defaultAttributeSetId);
        foreach ($defaultAttributeGroups as $attributeGroup) {
            $attributeGroupId = $attributeGroup->getAttributeGroupId();
            $attributes = self::getAttributesByGroup($attributeGroupId);
            $systemAttributes = \array_filter($attributes, function (AttributeInterface $attribute) {
                return !$attribute->getIsUserDefined();
            });
            if (empty($systemAttributes)) {
                continue;
            }
            $systemAttributes = \array_map(function (AttributeInterface $attribute) use ($attributeGroupId) {
                return self::convertEavAttribute($attribute, $attributeGroupId);
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
                    ->setAttributes($systemAttributes);
            } else {
                $attributes = \array_merge($expectedGroups[$attributeGroupCode]->getAttributes(), $systemAttributes);
                $expectedGroups[$attributeGroupCode]->setAttributes($attributes);
            }
        }

        return $expectedGroups;
    }

    private static function convertEavAttribute(AttributeInterface $attribute, int $attributeGroupId) : SnowIOAttributeInterface
    {
        foreach ($attribute->getAttributeSetInfo() as $attributeSetInfo) {
            if ($attributeSetInfo['group_id'] == $attributeGroupId) {
                return Bootstrap::getObjectManager()->create(SnowIOAttributeInterface::class)
                    ->setAttributeCode($attribute->getAttributeCode())
                    ->setSortOrder($attributeSetInfo['sort']);
            }
        }

        throw new \RuntimeException();
    }

    private static function assertAttributeGroupsAsExpected(array $expectedGroups, string $actualAttributeSetId)
    {
        $actualGroupsByCode = self::getAttributeGroups($actualAttributeSetId);

        self::assertSameSize(
            $expectedGroups,
            $actualGroupsByCode,
            \sprintf('Attribute set should have %s groups but actually has %s groups.', \count($expectedGroups), \count($actualGroupsByCode))
        );

        foreach ($expectedGroups as $groupCode => $expectedGroup) {
            $groupCode = $expectedGroup->getAttributeGroupCode();
            self::assertArrayHasKey($groupCode, $actualGroupsByCode, "Attribute set is missing group $groupCode.");
            self::assertAttributeGroupAsExpected($expectedGroup, $actualGroupsByCode[$groupCode]);
        }
    }

    private static function assertAttributeGroupAsExpected(AttributeGroupInterface $expected, Group $actual)
    {
        self::assertSame($expected->getName(), $actual->getAttributeGroupName());
        self::assertSame($expected->getAttributeGroupCode(), $actual->getAttributeGroupCode());

        if ($expected->getSortOrder() !== null) {
            self::assertSame($expected->getSortOrder(), (int)$actual->getSortOrder());
        }

        self::assertAttributesAsExpected($expected->getAttributes() ?? [], $actual->getAttributeGroupId());
    }

    /**
     * @param SnowIOAttributeInterface[] $expectedAttributes
     */
    private static function assertAttributesAsExpected(array $expectedAttributes, string $actualAttributeGroupId)
    {
        $actualAttributes = self::getAttributesByGroup($actualAttributeGroupId);

        self::assertSameSize(
            $expectedAttributes,
            $actualAttributes,
            \sprintf('Attribute group should have %s attributes but actually has %s attributes.', \count($expectedAttributes), \count($actualAttributes))
        );

        $actualAttributesByCode = [];
        foreach ($actualAttributes as $actualAttribute) {
            $actualAttributesByCode[$actualAttribute->getAttributeCode()] = $actualAttribute;
        }

        foreach ($expectedAttributes as $expectedAttribute) {
            $attributeCode = $expectedAttribute->getAttributeCode();
            self::assertArrayHasKey($attributeCode, $actualAttributesByCode, "Attribute group is missing attribute $attributeCode.");
            if ($expectedAttribute->getSortOrder() !== null) {
                self::assertEquals($expectedAttribute->getSortOrder(), $actualAttributesByCode[$attributeCode]->getSortOrder());
            }
        }
    }

    /**
     * @return Group[]
     */
    private static function getAttributeGroups(int $attributeSetId): array
    {
        $objectManager = Bootstrap::getObjectManager();
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
    private static function getAttributesByGroup(int $attributeGroupId): array
    {
        /** @var AttributeCollection $attributeCollection */
        $attributeCollection = Bootstrap::getObjectManager()->get(AttributeCollectionFactory::class)->create();
        $attributeCollection->setAttributeGroupFilter($attributeGroupId);
        $attributeCollection->addSetInfo();
        return $attributeCollection->getItems();
    }

    private static function removeAttributeSet(AttributeSetInterface $attributeSet)
    {
        $objectManager = Bootstrap::getObjectManager();
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
        $objectManager = Bootstrap::getObjectManager();
        /** @var Type $entityType */
        $entityType = $objectManager->create(Type::class)->loadByCode($entityTypeCode);
        return $entityType->getEntityTypeId();
    }

    private static function getDefaultAttributeSetId(string $entityTypeCode): int
    {
        $objectManager = Bootstrap::getObjectManager();
        /** @var EntityTypeCodeRepository $entityTypeCodeRepository */
        $entityTypeCodeRepository = $objectManager->get(EntityTypeCodeRepository::class);
        return $entityTypeCodeRepository->getDefaultAttributeSetId($entityTypeCode);
    }

    private static function getConfigurableProductData(string $name, array $configurableAttributes): ProductInterface
    {
        /** @var ProductInterface $product */
        $product = Bootstrap::getObjectManager()->create(ProductInterface::class);
        $product
            ->setSku('test-product-1')
            ->setPrice(3.00)
            ->setStatus(Status::STATUS_ENABLED)
            ->setName($name)
            ->setVisibility(Visibility::VISIBILITY_BOTH)
            ->setTypeId('configurable');
        $configurableProductOptions = self::createConfigurableProductOptions($configurableAttributes);
        $extensionAttributes = $product->getExtensionAttributes();
        $extensionAttributes->setConfigurableProductOptions($configurableProductOptions);
        $product->setExtensionAttributes($extensionAttributes);
        return $product;
    }

    private static function createConfigurableProductOptions(array $attributeCodes): array
    {
        $optionFactory = Bootstrap::getObjectManager()->get(OptionInterfaceFactory::class);
        /** @var OptionValueInterface $optionValue */
        $optionValue = Bootstrap::getObjectManager()->get(OptionValueInterfaceFactory::class)->create();
        $optionValue->setValueIndex(1);
        return \array_map(
            function (string $attributeCode) use ($optionFactory, $optionValue) {
                $attributeId = self::getAttributeId($attributeCode);
                /** @var OptionInterface $option */
                $option = $optionFactory->create();
                $option->setAttributeId($attributeId);
                $option->setLabel($attributeCode);
                $option->setValues([$optionValue]);
                return $option;
            },
            $attributeCodes
        );
    }

    private static function getAttributeId(string $attributeCode)
    {
        /** @var AttributeRepositoryInterface $attributeRepository */
        $attributeRepository = Bootstrap::getObjectManager()->get(AttributeRepositoryInterface::class);
        $attribute = $attributeRepository->get('catalog_product', $attributeCode);
        return $attribute->getAttributeId();
    }

    private static function saveNewProduct(ProductInterface $product)
    {
        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = Bootstrap::getObjectManager()->get(ProductRepositoryInterface::class);

        try {
            $productRepository->save($product);
        } catch (StateException $e) {
            throw new StateException(__($e->getMessage()), $e);
        }
    }

    private static function saveNewSizeAttribute()
    {
        /** @var $attribute \Magento\Catalog\Model\ResourceModel\Eav\Attribute */
        $attribute = Bootstrap::getObjectManager()->create(\Magento\Catalog\Model\ResourceModel\Eav\Attribute::class);
        
        /** @var AttributeRepositoryInterface $attributeRepository */
        $attributeRepository = Bootstrap::getObjectManager()->get(AttributeRepositoryInterface::class);

        $attribute->setData(
            [
                'attribute_code' => 'size',
                'entity_type_id' => 4,
                'is_global' => 1,
                'is_user_defined' => 1,
                'frontend_input' => 'select',
                'is_unique' => 0,
                'is_required' => 0,
                'is_searchable' => 0,
                'is_visible_in_advanced_search' => 0,
                'is_comparable' => 0,
                'is_filterable' => 0,
                'is_filterable_in_search' => 0,
                'is_used_for_promo_rules' => 0,
                'is_html_allowed_on_front' => 1,
                'is_visible_on_front' => 0,
                'used_in_product_listing' => 0,
                'used_for_sort_by' => 0,
                'frontend_label' => ['Size'],
                'backend_type' => 'int',
                'option' => [
                    'value' => ['option_0' => ['Option 1'], 'option_1' => ['Option 2']],
                    'order' => ['option_0' => 1, 'option_1' => 2],
                ],
            ]
        );

        $attributeRepository->save($attribute);
    }
}
