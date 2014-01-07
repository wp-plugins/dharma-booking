<?php    
include_once('../../../../../wp-config.php');
include_once('../functions.php');

global $wpdb;

$Vars = get_option('Dharma_Vars');

$sql = "SELECT G.name as Gname, G.phone,G.email, G.id,
                B.checkin,B.checkout,B.bookingtime,B.checkedin,
					I.payment, I.comment,I.clubcard,I.bookingdata,I.arivaltime,I.totaldue
         FROM `".$wpdb->prefix.DATABASE_PREFIX."bookings` B 
             LEFT JOIN ".$wpdb->prefix.DATABASE_PREFIX."guests G ON B.idguest = G.id 
             LEFT JOIN ".$wpdb->prefix.DATABASE_PREFIX."invoices I ON B.invoice = I.invoice 
         Where I.invoice=".$_POST['invoice'];
$data = $wpdb->get_row( $sql);
$data->nights  = findNonights($data->checkin,$data->checkout);
$data->bookingdata = unserialize($data->bookingdata); 

$sql="SELECT * FROM ".$wpdb->prefix.DATABASE_PREFIX."invoices I Where I.invoice=".$_POST['invoice'];
$paymentData = $wpdb->get_row( $sql);
$paymentHistory = unserialize($paymentData->paymentdata);

$sql="SELECT R.id, B.checkin,B.checkout, B.beds AS people, B.bookingtime, B.id AS bid,
                R.name, R.price, R.capacity
         FROM `".$wpdb->prefix.DATABASE_PREFIX."bookings` B 
             LEFT JOIN ".$wpdb->prefix.DATABASE_PREFIX."roomtypes R ON B.idroomtype = R.id
             LEFT JOIN ".$wpdb->prefix.DATABASE_PREFIX."invoices I ON B.invoice = I.invoice 
         Where I.invoice=".$_POST['invoice'].'
			ORDER BY R.menuorder	';		
$rentals = $wpdb->get_results( $sql,OBJECT_K);

$rentaltypes =$wpdb->get_results('SELECT price, id, name,discount,active FROM '.$wpdb->prefix.DATABASE_PREFIX.'roomtypes ORDER BY menuorder');

$sql = $wpdb->prepare('SELECT sum(B.beds) as beds, sum(I.payment) as payments 
FROM '.$wpdb->prefix.DATABASE_PREFIX.'bookings B
LEFT JOIN '.$wpdb->prefix.DATABASE_PREFIX.'invoices I ON B.invoice = I.invoice
LEFT JOIN '.$wpdb->prefix.DATABASE_PREFIX.'guests G ON G.id = B.idguest
WHERE G.id = %s', $data->id);
$guestData  = $wpdb->get_row($sql);

$sql = $wpdb->prepare('SELECT numberofnights,B.invoice,I.payment,I.totaldue  
FROM '.$wpdb->prefix.DATABASE_PREFIX.'bookings B 
LEFT JOIN '.$wpdb->prefix.DATABASE_PREFIX.'invoices I ON B.invoice = I.invoice
LEFT JOIN '.$wpdb->prefix.DATABASE_PREFIX.'guests G ON G.id = B.idguest WHERE G.id = %s GROUP BY B.invoice', $data->id);
foreach($wpdb->get_results($sql) as $v){	
	$numberOfNights  += $v->numberofnights;
	$totaldue  += $v->totaldue;
	$payments  += $v->payment;
	$hasHistory = ($v->invoice != $_POST['invoice']?true:false);
}//up i'd rather do it with just sql
$historyBalance = ($payments - $totaldue  );

foreach($rentaltypes as $rentaltype){
	$class = false;
	if($rentals[$rentaltype->id]){
		$rental = $rentals[$rentaltype->id];
		$class = ($rental->people > $rental->capacity ?'red':'green');
		$price = $rental->price * $rental->people * $data->nights ;

		$data->totalPeople += $rental->people;
	}elseif($rentaltype->active){
		if($rental->people) $rental->people = 0;
		$class = 'white';
	}
	
	if($data->checkedin && ($rental->people < 1) ) continue;

	if($class){
		$data->rentalItems .= '<div style="min-width:200px;float:left;	">'.
									($data->checkedin 
										? '<input type="hidden" value="'.$rental->people.'" name="rooms['.$rentaltype->id.']" /><span class="'.$class.'">'.$rental->people.'</span> '
										:'<input style="width: 47px;" type="number" min="0" class="'.$class.'" name="rooms['.$rentaltype->id.']" value="'.($rental->people?$rental->people:0).'" />'
									).'x '.($rentaltype->active?'<i>':'<b>').$rentaltype->name.($rentaltype->active?'</i>':'</b>').'</div>';
	}
}

$data->payed = money_format('%.2n',$data->payment);
$data->total = money_format('%.2n',$data->totaldue);
$data->balance  = ($data->payment - $data->totaldue);
$class = ($data->balance < 0 ? 'warning':'success');
$data->balanceDisp = '<span class="'.$class .'">'.$Vars['currancySymbol'].money_format('%.2n',$data->balance).'</span>';


?>
<h3><button type="button" class="cancelButton floatright xButton"><?=__('X',PLUGIN_TRANS_NAMESPACE)?></button></h3>	
<form method="POST"	id="update<?=$_POST['invoice']?>Form" >
	<input type="hidden" id="action" name="action" value="update" />
	<div class="detailsBoxFormGroup1">
		<div style="float:right">
			<div>
			<input  name="phone" value="<?=$data->phone?>"/>			
			<a href="tell:<?=$data->phone?>">
				<img src="<?=PLUGIN_ROOT_URL?>/img/phone.png" alt="<?=__('call phone',PLUGIN_TRANS_NAMESPACE)?>" />
			</a>
			</div>
			<input name="email" value="<?=$data->email?>"/>
			<a href="mailto:<?=$data->email?>">
				<img src="<?=PLUGIN_ROOT_URL?>/img/email.png" alt="<?=__('send email',PLUGIN_TRANS_NAMESPACE)?>" />
			</a>
		</div>
		<h1 id="infoName"><input  name="fullname" value="<?=$data->Gname?>" style="width: 380px;" /></h1>
		<?php if($hasHistory):?><strong>
			<?=__('Guest history ',PLUGIN_TRANS_NAMESPACE)?> : 
			<?=$guestData->beds?><img src="<?=PLUGIN_ROOT_URL?>/img/guests.png" alt="" title="<?=__('Guests',PLUGIN_TRANS_NAMESPACE)?>" />
			<?=$numberOfNights?> <img src="<?=PLUGIN_ROOT_URL?>/img/nights.png" alt="<?=__('Nights',PLUGIN_TRANS_NAMESPACE)?>" />
			<span class="<?=($historyBalance < 0 ? 'warning':'success')?>"><?=$Vars['currancySymbol'].$historyBalance?></span>
		</strong><?php endif ?>
		<div class="clear"></div>
		
		<h3 id="infoDates">
			checkin: <?php if($data->checkedin):?>
				<input  name="checkin" value="<?=$data->checkin?>" type="hidden" />
				<?=$data->checkin?>
			<?php else :  ?>
				<input  name="checkin" value="<?=$data->checkin?>" style="width: 80px;" />
			<?php endif?>
		checkout: <input name="checkout" value="<?=$data->checkout?>" style="width: 80px;" class="limiteddate"/>
			nights: <?=$data->nights?>
			<?php if(!$data->checkedin) : ?>
				<select name='time'>
					<?php foreach (explode(',',$Vars['arivalTime']) as $opt)  
						echo "<option value='$opt' ".selected( $data->arivaltime, $opt ).">$opt</option>";
					?>
				</select> 
			<?php endif ?>
		</h3> 
		<div style="max-width:600px; " ><?=$data->rentalItems?></div>
		<input type="hidden" name="invoice" class="invoiceid" value="<?=$_POST['invoice']?>" />
		<div class="clear"></div>
		<div  class="alignright detailsBoxFormGroup1	" >
			<strong>comment:</strong><br />
			<textarea name="comment" style="height:105px" ><?=$data->comment?></textarea>
		<h3><button type="button" class="saveButton alignright" id="update<?=$_POST['invoice']?>"><?=__('Update',PLUGIN_TRANS_NAMESPACE)?></button></h3>
	<small class="deleteButton" id="deleteButton"><?=__('Delete',PLUGIN_TRANS_NAMESPACE)?></small>

		</div>
	</div>
</form>	

<form method="POST" id="bookingDetailsForm"><h3 class="detailsBoxFormGroup2">
	<input type="hidden" name="invoice" class="invoiceid" value="<?=$_POST['invoice']?>" />
	<?=sprintf(__('%s member',PLUGIN_TRANS_NAMESPACE),'<img src="'.PLUGIN_ROOT_URL.'/img/discountcards/'.$Vars['discountCard'].'.png" />')?> : 
		<?=($data->clubcard?'<img src="'.PLUGIN_ROOT_URL.'/img/tick.png" />':'')?><br />
	<?=__('total',PLUGIN_TRANS_NAMESPACE)?>: <?=$data->total?><br />
	<?=__('payed',PLUGIN_TRANS_NAMESPACE)?>: <?=$data->payed?><br />
	<?=__('balance',PLUGIN_TRANS_NAMESPACE)?>: <?=$data->balanceDisp?><br />
	<?=__('payment',PLUGIN_TRANS_NAMESPACE)?>:<input type="number" name="payment" class="payment" style="width:82px;" value="0.00"/>
	<button class="paymentButton"><?=__('Make Payment',PLUGIN_TRANS_NAMESPACE)?></button>
   <small><a class="paymentHistoryButton"><?=__('payment history',PLUGIN_TRANS_NAMESPACE)?></a></small>
</form></h3>	

	

<div id="paymenthistorybox" class="hidden"> 
	<i><button class="closehistory">close</button></i>
	<h2>Invoice <?=$paymentData->invoice?></h2> 
	<h3>
		total due: <?=$paymentData->totaldue?>
		Payed <?=$paymentData->payment?>
		Updated: <?=$paymentData->updatetime?>
	</h3>
	<?php 
	foreach($paymentHistory as $anEvent){
	//var_dump($anEvent);
	switch ($anEvent['action']){
		case 'newbooking': 
		case 'update':
		  echo date('d/m/y h:m',$anEvent["updatetime"]).' <strong>'.$anEvent['action'].
					$anEvent["paymentdata"]["payed"].'</strong> ~ '.
					$anEvent["fullname"].' '.$anEvent["email"].' '.$anEvent["phone"].' '.$anEvent["checkin"].'-'. $anEvent["checkout"].
					'<br />';
				foreach($anEvent['rooms'] as $k => $v){
					if($v) echo $v.'x'.$rentaltypes[$k]->name.'-$'.$rentaltypes[$k]->price.',$'.$rentaltypes[$k]->discount.' ';
				}
				echo $anEvent['comment'].'<br />';
			break;
		case 'checkin':
			echo date('d/m/y h:m',$anEvent["updatetime"]).' <strong>checkin: $'.$anEvent["balancePos"].'</strong><br />';
			break;		
		case 'makepayment':
			echo date('d/m/y h:m',$anEvent["updatetime"]).' <strong>payment: $'.$anEvent["payment"].' = '.$anEvent["payedToDate"].'</strong><br />';
			break;	
	}
}
?>
</div>

<form action="" method="POST" id="deleteButtonForm">
	<input type="hidden" name="invoice" value="<?=$_POST['invoice']?>" />
	<input type="hidden" id="action" name="action" value="delete" />
</form>
