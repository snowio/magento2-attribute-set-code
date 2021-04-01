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
use Magento\Framework\Exception\StateException;
use Magento\Framework\ObjectManagerInterface;
use SnowIO\AttributeSetCode\Api\AttributeSetRepositoryInterface;
use SnowIO\AttributeSetCode\Model\AttributeSetCodeRepository;
use Magento\TestFramework\Helper\Bootstrap;

class ProductRepositoryPluginTest extends \PHPUnit\Framework\TestCase
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

    public function setUp() : void
    {
        Bootstrap::getInstance()->reinitialize();
        $this->objectManager = Bootstrap::getObjectManager();
        $this->attributeSetCodeRepository = $this->objectManager->get(AttributeSetCodeRepository::class);
        $this->extensionAttributeRepositoryFactory = $this->objectManager->get(ExtensionAttributesFactory::class);
        $this->productRepository = Bootstrap::getObjectManager()->get(ProductRepositoryInterface::class);
        $this->saveProductAttributeSet(self::ATTRIBUTE_SET_CODE);
        $this->attributeSetId = $this->getProductAttributeSetId(self::ATTRIBUTE_SET_CODE);
    }

    public function testAttributeSetCodeThatExists()
    {
        $product = $this->getProductData(__METHOD__);
        $product->setExtensionAttributes(
            $this->extensionAttributeRepositoryFactory->create(ProductInterface::class)
                ->setAttributeSetCode(self::ATTRIBUTE_SET_CODE)
        );
        $this->saveNewProduct($product);

        $loadedProduct = $this->productRepository->get($product->getSku());
        self::assertNotSame($product, $loadedProduct);
        self::assertEquals($this->attributeSetId, $product->getAttributeSetId());
    }

    public function testAttributeSetCodeThatDoesNotExist()
    {
        $product = $this->getProductData(__METHOD__);
        $product->setExtensionAttributes(
            $this->extensionAttributeRepositoryFactory->create(ProductInterface::class)
                ->setAttributeSetCode($nonExistentAttributeSetCode = 'non-existent-attribute-set')
        );
        try {
            $this->saveNewProduct($product);
            self::fail('Expected exception was not thrown');
        } catch (LocalizedException $e) {
            $expectedMessage = "The specified attribute set code $nonExistentAttributeSetCode does not exist";
            self::assertSame($expectedMessage, $e->getMessage());
        }
    }

    public function testAttributeSetId()
    {
        $product = $this->getProductData(__METHOD__)->setAttributeSetId($this->attributeSetId);
        $this->saveNewProduct($product);
    }

    public function testBothAttributeSetIdAndAttributeSetCodeSpecified()
    {
        $product = $this->getProductData(__METHOD__);
        $product->setAttributeSetId($this->attributeSetId);
        $product->setExtensionAttributes(
            $this->extensionAttributeRepositoryFactory->create(ProductInterface::class)
                ->setAttributeSetCode('non-existent-attribute-set-code')
        );
        $this->saveNewProduct($product);
        $loadedProduct = $this->productRepository->get($product->getSku());
        self::assertEquals($this->attributeSetId, $loadedProduct->getAttributeSetId());
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
            ->setName('My Test Attribute Set')
            ->setSortOrder(50);

        $objectManager = Bootstrap::getObjectManager();
        /** @var AttributeSetRepositoryInterface $attributeSetRepository */
        $attributeSetRepository = $objectManager->get(AttributeSetRepositoryInterface::class);
        $attributeSetRepository->save($attributeSet);
    }

    private function saveNewProduct(ProductInterface $product)
    {
        try {
            $this->productRepository->delete($product);
        } catch (StateException $e) {

        }
        $this->productRepository->save($product);
    }

    private function getProductData(string $name): ProductInterface
    {
        return Bootstrap::getObjectManager()->create(ProductInterface::class)
            ->setSku('test-product-1')
            ->setPrice(3.00)
            ->setStatus(Status::STATUS_ENABLED)
            ->setName($name)
            ->setVisibility(Visibility::VISIBILITY_BOTH)
            ->setTypeId(Type::TYPE_SIMPLE);
    }
}
