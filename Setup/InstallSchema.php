<?php

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{

    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        $table = $installer
            ->getConnection()
            ->newTable($installer->getTable('attribute_set_code'))
            ->addColumn(
                'attribute_set_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'primary' => true],
                'Attribute Set Id'
            )->addColumn(
                'attribute_set_code',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Attribute Set Code'
            )
            ->addIndex(
                $installer->getIdxName(
                    'attribute_set_code',
                    ['attribute_set_id', 'attribute_set_code'],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['attribute_set_id', 'attribute_set_code'],
                ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
            )
            ->addForeignKey(
                $installer->getFkName('attribute_set_code', 'attribute_set_id', 'eav_attribute_set', 'attribute_set_id'),
                'attribute_set_id',
                $installer->getTable('eav_attribute_set'),
                'attribute_set_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            );
        $installer->getConnection()->createTable($table);
        $installer->endSetup();
    }
}