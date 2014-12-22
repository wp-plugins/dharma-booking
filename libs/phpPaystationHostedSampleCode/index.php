<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>3 Party Paystation Hosted POST back Sample Code</title>
</head>

<body>

<h2>3 Party Paystation Hosted POST back Sample Code</h2>

<p>Please enter the amount you wish to pay in the space provided below.</p>
<form action="paystation_refresh.php"  method="post">

<label>Amount($):</label> <input type="text" name="amount"  /><input type="submit" value="Submit" />

</form>

<p>Test credit cards can be found <a href="http://paystation.co.nz/test-card-numbers" target="_blank">here</a>

<p>Use the following cent values to get the corresponding response from the bank emulator.
<ul style="list-style:none;">
<li>.00 - Transaction Successful</li>
.51 - Insufficient Funds</li>
<li>.54 - Expired Card</li>
<li>.57 - Transaction Type Not Supported</li>
<li>.75 - Bank Declined Transaction</li>
<li>.91 - Error Communicating with Bank</li>
</ul>
</p>

</body>
</html>
