<?php

namespace SnowIO\AttributeSetCode\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;

class AttributeGroupCodeRepository
{
    private $resourceConnection;
    private $dbAdapter;

    public function __construct(ResourceConnection $resourceConnection, AdapterInterface $dbAdapter = null)
    {
        $this->resourceConnection = $resourceConnection;
        $this->dbAdapter = $dbAdapter ?? $resourceConnection->getConnection();
    }

    public function getAttributeGroupId(string $attributeGroupCode, int $attributeSetId)
    {
        $select = $this->dbAdapter->select()
            ->from(['t' => $this->getAttributeGroupTableName()], 'attribute_group_id')
            ->where('t.attribute_group_code = ?', $attributeGroupCode)
            ->where('t.attribute_set_id = ?', $attributeSetId);

        $result = $this->dbAdapter->fetchOne($select);
        return $result ? (int) $result : null;
    }

    public function getAttributeGroupCode($attributeGroupId)
    {
        $select = $this->dbAdapter->select()
            ->from(['t' => $this->getAttributeGroupTableName()], 'attribute_group_code')
            ->where('t.attribute_group_id = ?', $attributeGroupId);

        $result = $this->dbAdapter->fetchOne($select);
        return $result ? $result : null;
    }

    public function getAttributeGroupIds(int $attributeSetId)
    {
        $select = $this->dbAdapter->select()
            ->from(['t' => $this->getAttributeGroupTableName()], 'attribute_group_id')
            ->where('t.attribute_set_id = ?', $attributeSetId);

        $result = $this->dbAdapter->fetchAll($select);
        return $result ? array_column($result, 'attribute_group_id') : null;
    }

    public function getAttributeGroupCodes($attributeSetCodes)
    {
        $select = $this->dbAdapter->select()
            ->from(['t' => $this->getAttributeGroupTableName()], 'attribute_group_id')
            ->join(['a' => $this->getAttributeSetCodeTableName()], 'a.attribute_set_id = t.attribute_set_id', [])
            ->where('a.attribute_set_code = ?', $attributeSetCodes);

        $result = $this->dbAdapter->fetchAll($select);
        return $result ? $result : null;
    }

    private function getAttributeSetCodeTableName()
    {
        return $this->resourceConnection->getTableName('attribute_set_code');
    }

    private function getAttributeSetTableName()
    {
        return $this->dbConnection->getTableName('eav_attribute_set');
    }

    private function getAttributeGroupTableName()
    {
        return $this->resourceConnection->getTableName('eav_attribute_group');
    }
}
