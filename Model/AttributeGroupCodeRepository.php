<?php

namespace SnowIO\AttributeSetCode\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;

class AttributeGroupCodeRepository
{
    private ResourceConnection $resourceConnection;
    private AdapterInterface $dbAdapter;

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

    public function getAttributeGroupIds(int $attributeSetId): array
    {
        $select = $this->dbAdapter->select()
            ->from(['t' => $this->getAttributeGroupTableName()], 'attribute_group_id')
            ->where('t.attribute_set_id = ?', $attributeSetId);

        return $this->dbAdapter->fetchCol($select);
    }

    private function getAttributeGroupTableName()
    {
        return $this->resourceConnection->getTableName('eav_attribute_group');
    }
}
