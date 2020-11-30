<?php
namespace SnowIO\AttributeSetCode\Model;

use Magento\Eav\Model\Entity\TypeFactory;

class EntityTypeCodeRepository
{
    private \Magento\Eav\Model\Entity\TypeFactory $entityTypeFactory;

    public function __construct(TypeFactory $entityTypeFactory)
    {
        $this->entityTypeFactory = $entityTypeFactory;
    }

    public function getEntityTypeId(string $code): int
    {
        return $this->entityTypeFactory->create()->loadByCode($code)->getEntityTypeId();
    }

    public function getDefaultAttributeSetId(string $code)
    {
        return $this->entityTypeFactory->create()->loadByCode($code)->getDefaultAttributeSetId();
    }
}
