<?php

function doIt($amount,$invoice){

	$amount		= ($amount * 100);
	$paystationId	= 'PAYSTATIONID'; //Paystation ID - Replace PAYSTATIONID with your Paystation ID.
	$gatewayId	= 'GATEWAYID'; //Gateway ID - Replace GATEWAYID with your GATEWAY ID.
	$merchantRef = urlencode($invoice); //merchant reference is optional, but is a great way to tie a transaction in with a customer (this is displayed in Paystation Administration when looking at transaction details). Max length is 64 char. Make sure you use it!
	$testMode = 'true'; //change this to 'false' for production transactions.

	/*
	Generating a unique ID for the transaction (ms)
	----------------------------------------------
	For this example we generate a random 8 char string using the makePaystationSessionID function. To make it truly unique you would need to store it in a database, and check this against future makePaystationSessionID's to make sure it is not repeated. There are different ways to generate a unique value - here are some other methods
		- Using php's timestamp function - mktime()
		- Using the primary key off a transaction table(NEED TO CHAT TO BRYAN ON THIS)
	the merchantsession (ms) can be up to 64 char long.
	*/

	$merchantSession = urlencode(time().'-'.makePaystationSessionID(8,8)); 
	$paystationUrl = "https://www.paystation.co.nz/direct/paystation.dll";
	$paystationParameters = "paystation=_empty&pstn_pi=".$paystationId."&pstn_gi=".$gatewayId."&pstn_ms=".$merchantSession."&pstn_mr=".$merchantRef."&pstn_am=".$amount."&pstn_nr=t";
	if 	($testMode == 'true'){
		$paystationParameters = $paystationParameters."&pstn_tm=t";
	}
	$result = doPostRequest($paystationUrl, $paystationParameters);

	/*Now we grab the details from the request we have just made - NOTE: it is best to store everything you can about a transaction - if you ever have to debug you will understand!  */

	$xmlData = new SimpleXMLElement($result);
	$digitalOrder = $xmlData->DigitalOrder; // The URL that we re-direct the customer too.
	$transactionID =  $xmlData->PaystationTransactionID;  //The transaction ID Paystation has just created.
	$PaymentRequestTime =  $xmlData->PaymentRequestTime; // The time that the transaction was initiated
	$DigitalOrderTime =  $xmlData->DigitalOrderTime;  //The time Paystation responds
	var_dump($xmlData );
}

/*----------------Functions Start----------------*/
function makePaystationSessionID($min=8,$max=8){

  # seed the random number generator - straight from PHP manual
  $seed = (double)microtime()*getrandmax();
  srand($seed);

  # make a string of $max characters with ASCII values of 40-122
  $p=0; while ($p < $max):
    $r=123-(rand()%75);
    $pass.=chr($r);
  $p++; endwhile;

  # get rid of all non-alphanumeric characters
  $pass=preg_replace("/[^a-zA-NP-Z1-9+]/","",$pass);

  # if string is too short, remake it
  if (strlen($pass)<$min):
    $pass=makePaystationSessionID($min,$max);
  endif;

  return $pass;

};

function doPostRequest($paystationUrl, $paystationParameters)
  {

    $paramArray=explode('&',$paystationParameters);
    foreach ($paramArray as $param) {
        $parts=explode('=',$param);
        $associativeArray[$parts[0]]=$parts[1];
    }

    $formattedData = http_build_query($associativeArray);

    $contextOptions = array (
        'http' => array (
            'method' => 'POST',
            'header'=> "Content-type: application/x-www-form-urlencoded\r\n"
                . "Content-Length: " . strlen($formattedData) . "\r\n",
            'content' => $formattedData
            )
        );
     $ctx = stream_context_create($contextOptions);
     $fp = @fopen($paystationUrl, 'r', false, $ctx);
     $response = @stream_get_contents($fp);
    
     return $response;
  }

 /*----------------Functions End----------------*/



/*
AT THIS POINT YOU SHOULD MAKE SURE YOU HAVE STORED ALL THE INFORMATION ABOUT THE TRANSACTION IN YOUR DATABASE. - THIS WILL HELP WITH ANY DEBUGGING YOU MAY NEED TO DO AT A LATER DATE.
*/

if ($digitalOrder) {
	header("Location:".$digitalOrder); //Successful initiation, so redirect to page given in digitalorder variable
	exit();
} else {
	echo "<pre>".htmlentities($result)."</pre>"; //no digitalorder variable, so initiation must have failed.  Print out xml packet for debugging purposes
}

