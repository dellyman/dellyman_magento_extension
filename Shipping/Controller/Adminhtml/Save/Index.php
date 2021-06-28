<?php
namespace Dellyman\Shipping\Controller\Adminhtml\Save;

use Magento\Backend\Block\Template;

class Index extends \Magento\Backend\App\Action
{
	protected $resultPageFactory;
	
	public function __construct(
		\Magento\Backend\App\Action\Context $context,
		\Magento\Framework\View\Result\PageFactory $resultPageFactory
	) {
		parent::__construct($context);
		$this->resultPageFactory = $resultPageFactory;
	}

	public function execute()
	{
		$resultPage = $this->resultPageFactory->create();
		// $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		// $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        // $connection = $resource->getConnection();
		// $tableName = $connection->getTableName('dellyman_shipping');
        // $query = "SELECT * FROM ". $tableName ." WHERE id = 1";
        // $check = $this->$resource->getConnection()->fetchOne($query);
		
		// $checkJson = json_decode($check,true);
	   return $resultPage;
	}
}
?>
