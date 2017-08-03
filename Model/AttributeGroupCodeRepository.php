<?php

namespace SnowIO\AttributeSetCode\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Model\ResourceModel\Db\Context;

class AttributeGroupCodeRepository
{
    private $dbConnection;

    public function __construct(Context $dbContext, $connectionName = null)
    {
        $connectionName = $connectionName ?: ResourceConnection::DEFAULT_CONNECTION;
        $this->dbConnection = $dbContext->getResources()->getConnection($connectionName);
    }

    public function getAttributeGroupId(string $attributeGroupCode, int $attributeSetId)
    {
        $select = $this->dbConnection->select()
            ->from(['t' => $this->getAttributeGroupTableName()], 'attribute_group_id')
            ->where('t.attribute_group_code = ?', $attributeGroupCode)
            ->where('t.attribute_set_id = ?', $attributeSetId);

        $result = $this->dbConnection->fetchOne($select);
        return $result ? (int) $result : null;
    }

    public function getAttributeGroupCode($attributeGroupId)
    {
        $select = $this->dbConnection->select()
            ->from(['t' => $this->getAttributeGroupTableName()], 'attribute_group_code')
            ->where('t.attribute_group_id = ?', $attributeGroupId);

        $result = $this->dbConnection->fetchOne($select);
        return $result ? $result : null;
    }

    public function getAttributeGroupIds(int $attributeSetId)
    {
        $select = $this->dbConnection->select()
            ->from(['t' => $this->getAttributeGroupTableName()], 'attribute_group_id')
            ->where('t.attribute_set_id = ?', $attributeSetId);

        $result = $this->dbConnection->fetchAll($select);
        return $result ? array_column($result, 'attribute_group_id') : null;
    }

    public function getAttributeGroupCodes($attributeSetCodes)
    {
        $select = $this->dbConnection->select()
            ->from(['t' => $this->getAttributeGroupTableName()], 'attribute_group_id')
            ->join(['a' => $this->getAttributeSetCodeTableName()], 'a.attribute_set_id = t.attribute_set_id', [])
            ->where('a.attribute_set_code = ?', $attributeSetCodes);

        $result = $this->dbConnection->fetchAll($select);
        return $result ? $result : null;
    }

    private function getAttributeSetCodeTableName()
    {
        return $this->dbConnection->getTableName('attribute_set_code');
    }

    private function getAttributeSetTableName()
    {
        return $this->dbConnection->getTableName('eav_attribute_set');
    }

    private function getAttributeGroupTableName()
    {
        return $this->dbConnection->getTableName('eav_attribute_group');
    }
}