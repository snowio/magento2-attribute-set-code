<?php

namespace SnowIO\Test\Integration\Plugin;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use SnowIO\AttributeSetCode\Api\CodedAttributeSetRepositoryInterface;
use SnowIO\AttributeSetCode\Model\AttributeSetCodeRepository;

class ProductRepositoryPluginTest extends \PHPUnit_Framework_TestCase
{
    const ATTRIBUTE_SET_CODE = 'test-attribute-set';

    /** @var  ObjectManagerInterface */
    private $objectManager;
    /** @var ExtensionAttributesFactory extensionAttributeRepositoryFactory */
    private $extensionAttributeRepositoryFactory;
    /** @var  AttributeSetCodeRepository */
    private $attributeSetCodeRepository;
    /** @var ProductRepositoryInterface */
    private $productRepository;
    private $attributeSetId;

    public function setUp()
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->attributeSetCodeRepository = $this->objectManager->get(AttributeSetCodeRepository::class);
        $this->extensionAttributeRepositoryFactory = $this->objectManager->get(ExtensionAttributesFactory::class);
        $this->productRepository = ObjectManager::getInstance()->get(ProductRepositoryInterface::class);
        $this->saveProductAttributeSet(self::ATTRIBUTE_SET_CODE);
        $this->attributeSetId = $this->getProductAttributeSetId(self::ATTRIBUTE_SET_CODE);
    }

    public function testAttributeSetCodeThatExists()
    {
        $product = $this->getProductData();
        $product->setExtensionAttributes(
            $this->extensionAttributeRepositoryFactory->create(ProductInterface::class)
                ->setAttributeSetCode(self::ATTRIBUTE_SET_CODE)
        );
        $this->productRepository->save($product);

        $loadedProduct = $this->productRepository->get($product->getSku());
        self::assertNotSame($product, $loadedProduct);
        self::assertEquals($this->attributeSetId, $product->getAttributeSetId());
    }

    /**
     * @expectedException LocalizedException
     * @expectedExceptionMessageRegExp /The specified attribute set code \w+ does not exist/
     */
    public function testAttributeSetCodeThatDoesNotExist()
    {
        $product = $this->getProductData();
        $product->setExtensionAttributes(
            $this->extensionAttributeRepositoryFactory->create(ProductInterface::class)
                ->setAttributeSetCode('non-existent-attribute-set')
        );
        $this->productRepository->save($product);
    }

    public function testAttributeSetId()
    {
        $product = $this->getProductData()->setAttributeSetId($this->attributeSetId);
        $this->productRepository->save($product);
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     */
    public function testBothAttributeSetIdAndAttributeSetCodeSpecified()
    {
        $product = $this->getProductData();
        $product->setAttributeSetId($this->attributeSetId);
        $product->setExtensionAttributes(
            $this->extensionAttributeRepositoryFactory->create(ProductInterface::class)
                ->setAttributeSetCode(self::ATTRIBUTE_SET_CODE)
        );
        $this->productRepository->save($product);
    }

    private function getProductAttributeSetId($attributeSetCode)
    {
        return $this->attributeSetCodeRepository->getAttributeSetId(4, $attributeSetCode);
    }

    private function saveProductAttributeSet($attributeSetCode)
    {
        $attributeSet = $this->objectManager->create(\SnowIO\AttributeSetCode\Api\Data\AttributeSetInterface::class)
            ->setEntityTypeCode('catalog_product')
            ->setAttributeSetCode($attributeSetCode)
            ->setName('My Test Attribute Set 1')
            ->setSortOrder(50);

        $objectManager = ObjectManager::getInstance();
        /** @var CodedAttributeSetRepositoryInterface $attributeSetRepository */
        $attributeSetRepository = $objectManager->get(CodedAttributeSetRepositoryInterface::class);
        $attributeSetRepository->save($attributeSet);
    }

    private function getProductData(): ProductInterface
    {
        return ObjectManager::getInstance()->create(ProductInterface::class)
            ->setSku('test-product-1')
            ->setPrice(3.00)
            ->setStatus(Status::STATUS_ENABLED)
            ->setName('Test product 1')
            ->setVisibility(Visibility::VISIBILITY_BOTH)
            ->setTypeId(Type::TYPE_SIMPLE);
    }
}