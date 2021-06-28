<?php
namespace Dellyman\Shipping\Controller\Adminhtml\SendOrder;

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
        $products = $this->getRequest()->getParam('products');
        $carrier = $this->getRequest()->getParam('carrier');

         $connection = $this->resourceConnection->getConnection();
         $tableName = $connection->getTableName('sales_order_address');
         $query = "SELECT street,lastname,city,email,telephone FROM ". $tableName ." WHERE parent_id =".$orderId." AND address_type = 'shipping'";
         $address = $this->resourceConnection->getConnection()->fetchAll($query); 
         $shipProducts = [];

         //Cycle through products
         foreach ($products as $key => $product) {
             $product = json_decode($product,true);
             array_push($shipProducts,$product);  
         }

         //Get product Names
         $allProductNames = "";
         foreach ($shipProducts as $key => $shipProduct) {
            if ($key == 0) {
                  $allProductNames = $shipProduct['name']."(". round($shipProduct['qty_shipped'])  .")";
           }else{
               $allProductNames = $allProductNames .",". $shipProduct['name']."(". round($shipProduct['qty_shipped']) .")";
           }
         }
         $productNames = "Total item(s)-". count($shipProducts) ." Products - " .$allProductNames;
         //login
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

         //Sending to dellyman API
        $date =  date("d/m/Y");
        $postdata = array( 
            'CustomerID' => $CustomerID,
            'PaymentMode' => 'pickup',
            'FixedDeliveryCharge' => 10,
            'Vehicle' => $carrier,
            'IsProductOrder' => 0,
            'BankCode' => "",
            'AccountNumber' => "",
            'IsProductInsurance' => 0,
            'InsuranceAmount' => 0,
            'PickUpContactName' => "Jason",
            'PickUpContactNumber' => "09035276989",
            'PickUpGooglePlaceAddress' => "Ikorodu  Rd" ,
            'PickUpLandmark' => "Mobile",	
            'PickUpRequestedTime' => "06 AM to 09 PM",
            'PickUpRequestedDate' => $date,
            'DeliveryRequestedTime' => "06 AM to 09 PM",
            'Packages' => [
                array(
                'DeliveryContactName' => $address[0]['lastname'],
                'DeliveryContactNumber' => $address[0]['telephone'],
                'DeliveryGooglePlaceAddress' => $address[0]['street'].",".$address[0]['city'],
                'DeliveryLandmark' => "",
                'PackageDescription' => $productNames,
                'ProductAmount' => "2000"
                )
            ],
            'CustomerAuth' => $CustomerAuth
        );
        $jsonPostData = json_encode($postdata);
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => 'http://206.189.199.89/api/v2.0/BookOrder',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $jsonPostData,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json'
        ),
        ));
        $responseJson = curl_exec($curl);
        $response = json_decode($responseJson,true);
        curl_close($curl);
        if ($response['ResponseCode'] == 100 ) {
            //Update Quantities
            foreach ($shipProducts as $key => $updateProduct) {
                //looking foa a variable
                $productId = $updateProduct['product_id'];
                $tableName = $connection->getTableName('sales_order_item');
                $query = "SELECT qty_shipped FROM ". $tableName ." WHERE product_id =".$productId." AND order_id =".$orderId;
                $oldqty = $this->resourceConnection->getConnection()->fetchAll($query); 
                $qty = $oldqty[0]['qty_shipped'] + $updateProduct['qty_shipped'] ;
                $connection = $this->resourceConnection->getConnection();
                $tableName = $connection->getTableName('sales_order_item');
                $query = "UPDATE ". $tableName ." SET qty_shipped = ". $qty ." WHERE product_id =".$productId." AND order_id =".$orderId;
                $connection->query($query); 
            }           
            $style = "success";
            $feedback = "Sucessfully sent #".$orderId." to dellyman, we will be coming for the pickup later in the day."."The Delivery ID is ".$response['Reference'];
        }
        else{
            $style = "error";
            $feedback = $response['ResponseMessage'];
        }
        $result = $this->resultJsonFactory->create();
        return $result->setData([
            "feedback" => $feedback,
            "style" => $style,
            "text" => $productNames
        ]);
    }
}

?>