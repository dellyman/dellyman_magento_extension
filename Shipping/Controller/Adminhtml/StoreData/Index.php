<?php
namespace Dellyman\Shipping\Controller\Adminhtml\StoreData;

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
        $email = $this->getRequest()->getParam('email');
        $password = $this->getRequest()->getParam('password');
        $result = $this->resultJsonFactory->create();

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
        if ($data['ResponseCode'] == 100) {
            $connection = $this->resourceConnection->getConnection();
            $tableName = $connection->getTableName('dellyman_shipping');
            $query = "SELECT * FROM ". $tableName ." WHERE id = 1";
            $check = $this->resourceConnection->getConnection()->fetchOne($query);
            if (empty($check)) {
                 //Insert 
                $query = "INSERT INTO ". $tableName ." (email,password) VALUES ('$email','$password')";
                $connection->query($query); 
            }else{
                //Update
                $query = "UPDATE ". $tableName ." SET email = '$email',password = '$password' WHERE id = 1";
                $connection->query($query); 
            }
            $message = "Successfully saved your login details";   
            $style = "success";
        }else{
            $message = "Invaild Login details";
            $style = "error";
        }
        return $result->setData([
            "message" => $message,
            "style" => $style
        ]);
    }
}

?>