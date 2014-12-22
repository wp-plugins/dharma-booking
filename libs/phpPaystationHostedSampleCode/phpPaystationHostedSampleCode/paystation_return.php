<?php

$merchantSession = $_GET['ms'];
$transactionId = $_GET['ti'];
$amount = $_GET['am'];
$errorCode = $_GET['ec'];
$errorMessage = $_GET['em'];
$cardNumber = $_GET['cardno'];
$cardExpiry = $_GET['cardexp'];
$merchantRef = $_GET['merchant_ref'];

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>3 Party Paystation Hosted POST back Sample Code</title>
</head>

<body>
<h2> 3 Party Paystation Hosted Return Page Sample Code</h2>
<?php
$amount = $amount / 100;
if($errorCode == '0'){
echo "<p>Your transaction was successful</p>";
echo "Amount: $".$amount."<br />";
echo "Transaction ID: ".$transactionId."<br />";
echo "Card Used: ".$cardNumber."<br />";
echo "Card Expiry Date: ".$cardExpiry."<br />";

}else{
echo "<p>Transaction Error: <strong>".$errorMessage."</strong></p>";
echo "Amount: $".$amount."<br />";
echo "Transaction ID: ".$transactionId."<br />";
echo "Card Used: ".$cardNumber."<br />";
echo "Card Expiry Date: ".$cardExpiry."<br />";
	
}

?>
</body>
</html>