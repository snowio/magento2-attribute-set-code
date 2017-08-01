<?php

namespace SnowIO\AttributeSetCode\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Model\ResourceModel\Db\Context;

class AttributeSetCodeRepository
{
    private $dbConnection;

    public function __construct(Context $dbContext, $connectionName = null)
    {
        $connectionName = $connectionName ?: ResourceConnection::DEFAULT_CONNECTION;
        $this->dbConnection = $dbContext->getResources()->getConnection($connectionName);
    }

    public function getAttributeSetId(int $entityTypeId, string $attributeSetCode)
    {
        $select = $this->dbConnection->select()
            ->from(['c' => $this->getAttributeSetCodeTableName()], 'attribute_set_id')
            ->join(['a' => $this->getAttributeSetTableName()], 'a.attribute_set_id=c.attribute_set_id', [])
            ->where('c.attribute_set_code = ?', $attributeSetCode)
            ->where('a.entity_type_id = ?', $entityTypeId);

        $result = $this->dbConnection->fetchOne($select);
        return $result ? (int) $result : null;
    }

    public function getAttributeSetCode(int $attributeSetId)
    {
        $select = $this->dbConnection->select()
            ->from(['t' => $this->getAttributeSetCodeTableName()], 'attribute_set_code')
            ->where('t.attribute_set_id = ?', $attributeSetId);

        $result = $this->dbConnection->fetchOne($select);
        return $result ? $result : null;
    }

    public function setAttributeSetCode(int $attributeSetId, string $attributeSetCode)
    {
        $this->dbConnection->insert($this->getAttributeSetCodeTableName(),[
            'attribute_set_id' => $attributeSetId,
            'attribute_set_code' => $attributeSetCode
        ]);
    }

    private function getAttributeSetCodeTableName()
    {
        return $this->dbConnection->getTableName('attribute_set_code');
    }

    private function getAttributeSetTableName()
    {
        return $this->dbConnection->getTableName('eav_attribute_set');
    }
}