<?php
namespace Dellyman\Shipping\Controller\Adminhtml\GetOrder;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\ResourceConnection;

class Index extends \Magento\Backend\App\Action
{
    /** 
    * @var /Magento\Framework\Controller\Result\JsonFactory
    */

    protected $resultJsonFactory; 
    protected $resourceConnection;
    /**
     * @param Context     $context
     * @param JsonFactory $JsonFactory
     */
    public function __construct(
        Context $context,
        ResourceConnection $resourceConnection,
        JsonFactory $resultJsonFactory
        )
    {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory; 
        $this->resourceConnection = $resourceConnection;
    }


    public function execute(){
        $orderId = $this->getRequest()->getParam('order');
        $result = $this->resultJsonFactory->create();
        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName('sales_order_item');
        $query = "SELECT product_id,name,sku,qty_ordered,price,qty_shipped FROM ". $tableName ." WHERE order_id =".$orderId;
        $products = $this->resourceConnection->getConnection()->fetchAll($query);
        // $order = $this->_objectManager->create('Magento\Sales\Api\OrderRepositoryInterface')->getOrderData($orderId);
        // $orderItems = $order->getData();

        //Sort order for variance
        $orders = [];
        foreach ($products as $key => $product) {
            if ($product['price'] != 0) {
                array_push($orders,$product);            
            }else{}
        }
        
         
        return $result->setData([
            "order" => $orderId,
            "orderItems" => $orders,
        ]);
    }
}

?>