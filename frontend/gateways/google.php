<?php

function makePaymentForm($acount,$currency,$payment){
   ?>
<form method="POST" action="https://checkout.google.com/api/checkout/v2/checkoutForm/Merchant/<?=$gateway?>" accept-charset="utf-8">

  <input type="hiden" name="item_name_1" value="<?=__('Booking with earthling.za.org',PLUGIN_TRANS_NAMESPACE)?>"/>
  <input type="hiden" name="item_description_1" class="gatewaydiscription" value="Chunky peanut butter."/>
  <input type="hiden" name="item_quantity_1" value="1"/>
  <input type="hiden" name="item_price_1" id="gatewayprice<?=$payment?>" value=""/>
  <input type="hiden" name="item_currency_1" value="<?=$currency?>"/>

  <input type="hidden" name="_charset_"/>

  <input type="image" name="Google Checkout" alt="Fast checkout through Google" src="http://checkout.google.com/buttons/checkout.gif?merchant_id=1234567890&w=180&h=46&style=white&variant=text&loc=en_US"
height="46" width="180"/>

</form>
<?php 

}

?>