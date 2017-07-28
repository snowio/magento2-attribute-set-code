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

    public function getAttributeSetId($attributeSetCode)
    {
        $select = $this->dbConnection->select()
            ->from(['t' => $this->getAttributeSetCodeTableName()], 'attribute_set_id')
            ->where('t.attribute_set_code = ?', $attributeSetCode);

        $result = $this->dbConnection->fetchOne($select);
        return $result ? (int) $result : null;
    }

    public function getAttributeSetCode($attributeSetId)
    {
        $select = $this->dbConnection->select()
            ->from(['t' => $this->getAttributeSetCodeTableName()], 'attribute_set_code')
            ->where('t.attribute_set_id = ?', $attributeSetId);

        $result = $this->dbConnection->fetchOne($select);
        return $result ? $result : null;
    }

    public function removeAttributeSet($attributeSetCode)
    {
        $this->dbConnection->delete($this->getAttributeSetCodeTableName(), [
            'attribute_set_code' => $attributeSetCode
        ]);
    }

    public function setAttributeSetId($attributeSetId, $attributeSetCode)
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
}