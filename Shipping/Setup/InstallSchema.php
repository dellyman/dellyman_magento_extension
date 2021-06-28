<?php
namespace Dellyman\Shipping\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    
  public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
  {
      
    $newsTableName = $setup->getTable('dellyman_shipping');

    if($setup->getConnection()->isTableExists($newsTableName) != true) {

      $newsTable = $setup->getConnection()
          ->newTable($newsTableName)
          ->addColumn(
              'id',
              \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
              null,
              ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
              'Id'
          )
          ->addColumn(
              'email',
              \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
              255,
              ['nullable' => false, 'default' => ''],
                'Email'
          )
          ->addColumn(
              'password',
              \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
              null,
              ['nullable' => false, 'default' => ''],
                'password'
          )
          ->addColumn(
              'created_at',
              \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
              null,
              ['nullable' => false],
                'Created At'
          )
          ->addColumn(
              'updated_at',
              \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
              null,
              ['nullable' => false],
                'Updated At'
          )
          ->addIndex(
            $setup->getIdxName('dellyman_shipping', ['email']),
            ['email']
          )
          ->setComment("Dellyman Table");

      $setup->getConnection()->createTable($newsTable);
    }
  }
}
?>
