<?php
/*
This is the POST back sample file.  This should show you the basics of POST back with Paystation.
This file should be trusted for the actual return values and should be used to update your database with the results values.
PLEASE NOTE:  The POST back function from Paystation will not work on local networks that cannot be accessed externally.
*/

$postData = file_get_contents("php://input"); //Get the contents of the XML packet that has been POSTed back from Paystation.
       
$xml = simplexml_load_string($postData);  // Create an XML string
        
//Create variables from the XML packet.
$errorCode = $xml->ec;
$errorMessage = $xml->em;
$transactionId = $xml->ti;
$cardType = $xml->ct;
$merchantReference = $xml->merchant_ref;
$testMode = $xml->tm;
$merchantSession = $xml->MerchantSession;
$usedAcquirerMerchantId = $xml->UsedAcquirerMerchantID;
$amount = $xml->PurchaseAmount; // Note this is in cents
$transactionTime = $xml->TransactionTime;
$requestIp = $xml->RequestIP;

/*

There are two basic functions below:
- CreateXMLFile creates a XML file called test.xml with the data. Note: the folder you are creating the file in will need to be chmod set to 777.
- sendemail sends an email with the PHP variables created from the XML packet.

These functions allow you to test the basics of the POST back XML packet however you should store the data in a database.

*/

function CreateXMLFile($xml){

$xmlfile = $xml;
$xmlfile->asXML("test.xml"); // Use this to create a XML file with the XML content sent from Paystation.  Remove the // at the begining of the line to use.

}

function sendXMLFile($postData){

$message = $postData;

$to = "chris@paystation.co.nz";//Change this to your email
$subject = "Transaction Results for ".$MerchantReference;
$from = "email@example.com";//Change this to the sender email
$headers = "From:" . $from;
mail($to,$subject,$message,$headers);

}

function sendEmail($errorCode, $errorMessage, $transactionId, $cardType, $merchantReference, $testMode, $merchantSession, $usedAcquirerMerchantId, $amount, $transactionTime, $requestIp)
{
$message = "Error Code: ".$errorCode."\r\n";
$message .= "Error Message: ".$errorMessage."\r\n";
$message .= "Transaction ID: ".$transactionId."\r\n";
$message .= "Card Type: ".$cardType."\r\n";
$message .= "Merchant Reference: ".$merchantReference."\r\n";
$message .= "Test Mode: ".$TestMode."\r\n";
$message .= "Merchant Session: ".$merchantSession."\r\n";
$message .= "Merchant ID: ".$usedAcquirerMerchantId."\r\n";
$message .= "Amount: ".$amount."\r\n";
$message .= "Transaction Time: ".$transactionTime."\r\n";
$message .= "IP: ".$requestIp."\r\n";

$to = "email@example.com";//Change this to your email
$subject = "Transaction Results for ".$MerchantReference;
$from = "email@example.com";//Change this to the sender email
$headers = "From:" . $from;
mail($to,$subject,$message,$headers);
}


//Calling the functions - To call a different function uncomment the function(remove the  // from the beggining of the line)
//CreateXMLFile($xml);
//sendEmail($errorCode, $errorMessage, $transactionId, $cardType, $merchantReference, $testMode, $merchantSession, $usedAcquirerMerchantId, $amount, $transactionTime, $requestIp);
//sendXMLFile($postdata);

?>