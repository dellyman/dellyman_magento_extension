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
        //Login to get authenciation
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();
        $tableName = $resource->getTableName('dellyman_shipping');
        $query = "SELECT * FROM ". $tableName;
        $check = $connection->fetchAll($query);
        $email = $check[0]['email'];
        $password = $check[0]['password']; 
        
        $ch = curl_init();
        $url = "http://206.189.199.89/api/v2.0/Login";
        curl_setopt($ch, CURLOPT_URL,  $url);
        curl_setopt($ch, CURLOPT_ENCODING, " ");
        curl_setopt($ch,  CURLOPT_TIMEOUT,  0);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        // curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json"
        ));
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array(
                "Email" => $email,
                "Password"=> $password
        )));
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2TLS);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $response = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($response,true);
        
        $CustomerAuth = $data['CustomerAuth'];
        $CustomerID = $data['CustomerID']; 
        
        
        // Check if order has failed and credit the shipped quantity
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();
        $tableName = $resource->getTableName('dellyman_shipping_orders');
        $query = "SELECT * FROM ". $tableName." WHERE order_id = ". $orderId;
        $orders = $connection->fetchAll($query);  

        //Run for-loop to check status of orders
        foreach ($orders as $key => $order) {
            $dellyman_order = intval($order['dellyman_order_id']);
            $curl = curl_init();
            curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://206.189.199.89/api/v2.0/TrackOrder',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode([
                    "CustomerID" => $CustomerID,
                    "CustomerAuth" => $CustomerAuth,
                    "OrderID" => $dellyman_order
            ]),
            CURLOPT_HTTPHEADER => array(
            "Content-Type: application/json"
            )));
            $response = curl_exec($curl);
            $status = json_decode($response,true);
            curl_close($curl);
            if ($status['OrderStatus'] == 'CANCELLED' AND is_null($order['is_TrackBack']) ) {
                 $products = json_decode($order['products_shipped'],true);
                 
                 //Update quantity in a order
                 foreach ($products as $key => $product) {
                     //looking foa a variable
                    $productId = $product['product_id'];
                    $sku = strval($product['sku']);
                    $tableName = $connection->getTableName('sales_order_item');
                    $query = "SELECT qty_shipped FROM ". $tableName ." WHERE product_id =".$productId." AND order_id =".$orderId." AND price != 0";
                    $oldqty = $this->resourceConnection->getConnection()->fetchAll($query); 
                    $qty = $oldqty[0]['qty_shipped'] - $product['qty_shipped'] ;
                    $connection = $this->resourceConnection->getConnection();
                    $tableName = $connection->getTableName('sales_order_item');
                    $query = "UPDATE ". $tableName ." SET qty_shipped = ". $qty ." WHERE product_id =".$productId." AND order_id =".$orderId." AND price != 0";
                    $connection->query($query); 
                 }
                $connection = $this->resourceConnection->getConnection();
                $tableName = $connection->getTableName('dellyman_shipping_orders');
                $query = "UPDATE ". $tableName ." SET is_TrackBack = 1 WHERE dellyman_order_id = ".$dellyman_order;
                $connection->query($query);
            }
        }
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