<?php

namespace SnowIO\AttributeSetCode\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;

class AttributeSetCodeRepository
{
    private $resourceConnection;
    private $dbAdapter;

    public function __construct(ResourceConnection $resourceConnection, AdapterInterface $dbAdapter = null)
    {
        $this->resourceConnection = $resourceConnection;
        $this->dbAdapter = $dbAdapter ?? $resourceConnection->getConnection();
    }

    public function getAttributeSetId(int $entityTypeId, string $attributeSetCode)
    {
        $select = $this->dbAdapter->select()
            ->from(['c' => $this->getAttributeSetCodeTableName()], 'attribute_set_id')
            ->join(['a' => $this->getAttributeSetTableName()], 'a.attribute_set_id=c.attribute_set_id', [])
            ->where('c.attribute_set_code = ?', $attributeSetCode)
            ->where('a.entity_type_id = ?', $entityTypeId);

        $result = $this->dbAdapter->fetchOne($select);
        return $result ? (int) $result : null;
    }

    public function getAttributeSetCode(int $attributeSetId)
    {
        $select = $this->dbAdapter->select()
            ->from(['t' => $this->getAttributeSetCodeTableName()], 'attribute_set_code')
            ->where('t.attribute_set_id = ?', $attributeSetId);

        $result = $this->dbAdapter->fetchOne($select);
        return $result ? $result : null;
    }

    public function setAttributeSetCode(int $attributeSetId, string $attributeSetCode)
    {
        $this->dbAdapter->insert($this->getAttributeSetCodeTableName(),[
            'attribute_set_id' => $attributeSetId,
            'attribute_set_code' => $attributeSetCode
        ]);
    }

    private function getAttributeSetCodeTableName()
    {
        return $this->resourceConnection->getTableName('attribute_set_code');
    }

    private function getAttributeSetTableName()
    {
        return $this->resourceConnection->getTableName('eav_attribute_set');
    }
}
