<?php
require_once('reports.php');
require_once('reports/occupacity.php');

class checkinDashboard extends dharmaAdmin{
	function checkinDashboard(){
		echo '<div id="'.PLUGIN_TRANS_NAMESPACE.'-containmentDiv" >';
		$this->days = 180;
		$this->doPostActions();
		$this->setupVariables();
		$this->echoDashboardSetup();
		$this->displayMainBody();
		$this->displayHiddenBoxes();
		echo '</div>';
	}
	function setupVariables(){
	}
	/*displays the main body of the dashboard*/
	function displayMainBody(){ 
		$from =  date('Y-m-d',time() + 86400 );
		global $wpdb;
		$Vars = get_option('Dharma_Vars');
		$sql = "
        SELECT G.name as Gname, G.phone, G.email , G.id,
                B.checkin,B.checkout,B.checkedin, SUM( B.beds ) as totalrentals,
                I.invoice,I.payment,I.comment,I.totaldue,I.arivaltime
         FROM `".$wpdb->prefix.DATABASE_PREFIX."bookings` B 
             LEFT JOIN ".$wpdb->prefix.DATABASE_PREFIX."guests G ON B.idguest = G.id 
             LEFT JOIN ".$wpdb->prefix.DATABASE_PREFIX."invoices I ON B.invoice = I.invoice 
         Where G.id <> 1 
				AND (DATE('$from') BETWEEN B.checkin AND B.checkout
            OR ADDDATE('$from', ".$this->days.") BETWEEN B.checkin AND B.checkout
            OR (B.checkin >= DATE('$from') AND B.checkout <= ADDDATE('$from', ".$this->days.")))	
			group BY B.checkin,I.invoice, G.id";
		$data = $wpdb->get_results( $sql);
		
	?><div id="rentalcheckinbox"><?php
	foreach ($data as $aguest ): 
		$aguest->rentalids = array();
		$aguest->totalPrice =0;
		$aguest->rentalItems = '';
		
		
		$aguest->totalPeople = $aguest->totalrentals;
			
		$aguest->nights  = findNonights($aguest->checkin,$aguest ->checkout);
			
		$sql=$wpdb->prepare("SELECT B.checkin,B.checkout, B.beds AS people,R.discount, R.name, R.price, R.capacity
         FROM `".$wpdb->prefix.DATABASE_PREFIX."bookings` B 
             LEFT JOIN ".$wpdb->prefix.DATABASE_PREFIX."roomtypes R ON B.idroomtype = R.id
             LEFT JOIN ".$wpdb->prefix.DATABASE_PREFIX."invoices I ON B.invoice = I.invoice 
         Where I.invoice=%s ORDER BY R.menuorder",$aguest->invoice);
		$rentals = $wpdb->get_results( $sql);
		
		$aguest->totalPrice = 0; 
		$aguest->totalDiscount = 0; 
		foreach($rentals as $rental){
	      $overCap = ($rental->people > $rental->capacity ?'warning':'');
			$price = $rental->price * $rental->people * 	$aguest->nights ;
			$aguest->totalDiscount += $rental->discount * $rental->people  ;
			$aguest->rentalItems .= '<li><span class="'.$overCap.'" >'.$rental->people.' </span> x <i>'.$rental->name.'</i></li>';
			$aguest->rentalTotals[$rental->name] += $rental->people;
		}
		$aguest->totalDiscount *=  	$aguest->nights;
		$aguest->payed = $aguest->payment;
		$aguest->total = $aguest->totaldue;
		$aguest->balance  = ($aguest->payment - $aguest->totaldue);
		$class = ($aguest->balance < 0 ? 'warning':'success');
		$aguest->balanceDisp = '<span class="'.$class .'">$'.$aguest->balance.'</span>';
		$aguest->balancePos = $aguest->totaldue - $aguest->payment;
		
		unset($clientPaymentDetails);
		$sql = "SELECT SUM(I.payment) AS payed, SUM(I.totaldue) AS totaldue
					FROM `".$wpdb->prefix.DATABASE_PREFIX."bookings` B LEFT JOIN ".$wpdb->prefix.DATABASE_PREFIX."invoices I ON B.invoice = I.invoice WHERE B.idguest =".$aguest->id;
		$clientPaymentDetails = $wpdb->get_row( $sql);
		$class = ($clientBalance < 0 ? 'warning':'success');
		
		if($aguest->checkedin) $aguest->nights  = intval((strtotime($aguest ->checkout) - time())/86400) + 1; 
		
		if(time() > strtotime($aguest->checkin)){
				if(!$istoday)		$aguest->longdate= '<h1 class="clear longdate">Today '.date( 'l, jS F',time()).'</h1>';
					$istoday = true;
			}else{
				$istoday = false;
				if($theDay != $aguest->checkin)
					$aguest->longdate = '<h1 class="clear longdate">'.date( 'l, jS F Y',strtotime($aguest->checkin)).'</h1>';
				$theDay = $aguest->checkin; 
			}
		?>	
		<?=$aguest->longdate?>

		<div class="alignleft groupingBox <?=($aguest->checkedin?'checkedin':'')?>" >
		
			<div class="center rentalDetails">
				<h1><?=$aguest->Gname?></h1>
				
				<h4><button class="detailsButton" id="booking-<?=$aguest->invoice?>"  title="<?=sprintf(__('Booking details for %s.',PLUGIN_TRANS_NAMESPACE),$aguest->Gname)?>" >
					<img src="<?=PLUGIN_ROOT_URL?>/img/details.png" alt="<?=__('Details',PLUGIN_TRANS_NAMESPACE)?>" />
				</button></h4>
				<ul class='details'>
					<li title="<?=($aguest->checkedin?__('nights remaining till checkout',PLUGIN_TRANS_NAMESPACE):__('total nights',PLUGIN_TRANS_NAMESPACE))?>">
						<?=$aguest->nights?> <img src="<?=PLUGIN_ROOT_URL?>/img/nights.png" alt="<?=__('Nights',PLUGIN_TRANS_NAMESPACE)?>" />
					</li>
					<li title="<?=__('total guests',PLUGIN_TRANS_NAMESPACE)?>">
						<?=$aguest->totalPeople?><img src="<?=PLUGIN_ROOT_URL?>/img/guests.png" alt="" title="<?=__('Guests',PLUGIN_TRANS_NAMESPACE)?>" />
					</li>
					<?php if($aguest->checkedin): ?>
						<li title="<?=__('balance on this invoice',PLUGIN_TRANS_NAMESPACE)?>"><?=$aguest->balanceDisp ?></li>
					<?php endif ?>	
				</ul>
				
				<?php if(!$aguest->checkedin): ?><ul class="money">
						<li title="<?=__('total due without discount card',PLUGIN_TRANS_NAMESPACE)?>">$<?=$aguest->total-$aguest->payment ?></li>
						<?php if($Vars['discountCard'] != 'none'):?>
							<li title="<?=__('total due with discount card',PLUGIN_TRANS_NAMESPACE)?>">
							<img src="<?=PLUGIN_ROOT_URL?>img/discountcards/<?=$Vars['discountCard']?>.png" />$<?=$aguest->totalDiscount-$aguest->payment ?></li>
						<?php endif ?>
				</ul>	<?php endif ?>
				
				<?php if(!$aguest->checkedin && $istoday):?>
					<h3><button  type="button" class="checkinButton"><?=__('Checkin',PLUGIN_TRANS_NAMESPACE)?></button></h3>
				<?php endif ?>
				
				<input type="hidden"   class="invoice" value="<?=$aguest->invoice ?>" />
				<input type="hidden"   class="totaldue" value="<?=$aguest->totalPrice ?>" />
				<input type="hidden"   class="balancePos" value="<?=$aguest->balancePos ?>" />
				<input type="hidden"   class="discountdue" value="<?=($aguest->totalDiscount-$aguest->payment) ?>" />
			</div>
			<?=($aguest->checkedin?'':'<ul class="rentalitems">'.$aguest->rentalItems.'</ul>')?>
			<?php if($aguest->comment && !$aguest->checkedin):?>
				<p><?=$aguest->comment?></p>
			<?php endif ?>
			<?php 
				$arivaltime = (strtotime($aguest->arivaltime.' '.$aguest->checkin)?strtotime($aguest->arivaltime.' '.$aguest->checkin):strtotime($aguest->arivaltime.' '.$aguest->checkin));
			?>
			<?php if(!$aguest->checkedin && strtotime('now') > $arivaltime && strtotime($aguest->checkin) < time()): ?>
				<h3 title="expected <?=$aguest->checkin?>" class="late"><?=__('late',PLUGIN_TRANS_NAMESPACE)?></h3>
			<?php endif ?>
			<?php $checkinDate = $aguest->checkin; ?>
		</div>
		<?php endforeach ?>
		</div>
		<?php
	}


	function displayHiddenBoxes(){
		global $wpdb, $checkinpage ;
		$sql="SELECT R.name,R.id,R.price,R.discount,R.minimum,R.capacity FROM ".$wpdb->prefix.DATABASE_PREFIX."roomtypes R WHERE R.active = 1 ORDER BY R.menuorder";
		$rentals = $wpdb->get_results( $sql);
		$Vars = get_option('Dharma_Vars');
		?>
	<div id="checkinDiv" class="hidden popupup-box">
			<h3><button type="button" class="cancelButton floatright xButton"><?=__('X',PLUGIN_TRANS_NAMESPACE)?></button></h3>	

		<form action="" method="POST" id="checkinButtonForm"  >
			<input type="hidden" id="action" name="action" value="checkin" />
			<input type="hidden" id="checkinInvoice" name="invoice" />
			<input type="hidden" id="discountdue" name="nodiscount" />
			<input type="hidden" id="totaldue" name="discounted" />
			<h1><?=sprintf(__('Check in %s.',PLUGIN_TRANS_NAMESPACE),'<strong id="checkinName"></strong>');?></h1>
			<h3>
				<?=__('Total due',PLUGIN_TRANS_NAMESPACE)?>: $<span id="checkinDueLabel"></span>
				<?php if($Vars['discountCard']):
					$cardimge = '<img src="'.PLUGIN_ROOT_URL.'img/discountcards/'.$Vars['discountCard'].'.png" title="'.sprintf(__('%s discount card',PLUGIN_TRANS_NAMESPACE),$Vars['discountCard']).'"/>' ?>
					~ 
					<?=sprintf(__('Total due with %s',PLUGIN_TRANS_NAMESPACE),$cardimge);?>
					: $<span id="checkinDiscountLabel"></span>
				<br />
				<input type="checkbox" name="clubcard" id="checkinclubcard" /> <?=sprintf(__('Guest is a %s member?',PLUGIN_TRANS_NAMESPACE),$cardimge);?>
				<?php endif ?>
			</h3>
			<h2><?=__('Payment of',PLUGIN_TRANS_NAMESPACE)?> : <input type="number" id="checkinDue" name="payment" style="width:auto;height:auto;" /></h2>
			<h2>
				<button	 type="button"  id="checkinButton" class="saveButton" 	><?=__('Check in',PLUGIN_TRANS_NAMESPACE)?></button>
			</h2>
		</form> 
	</div>

	<div id="newbookingdiv" class="hidden popupup-box">
		<h3><button type="button" class="cancelButton floatright xButton"><?=__('X',PLUGIN_TRANS_NAMESPACE)?></button></h3>	
		<form action="" method="POST" id="newbookingForm">
			<input type="hidden" id="action" name="action" value="newbooking" />
			<div class="alignright " style="width: 240px;" id="rentals-selectors">
				<?php foreach($rentals as $rental) : ?>
					<div style="min-width:200px;float:left;	">
						<input style="width:37px;"	 type="number" name="rooms[<?=$rental->id?>]"  min="0" /><strong><?=$rental->name?></strong> : 
						<?=$rental->minimum?> - <?=$rental->capacity?> <img src="<?=PLUGIN_ROOT_URL?>/img/guests.png" alt="" title="<?=__('Guests',PLUGIN_TRANS_NAMESPACE)?>" />
						<span class="price hidden"><?=$rental->price?></span><span class="hidden discount"><?=$rental->discount?></span>
					</div>
				<?endforeach?>
			</div>
				<div class="floatleft">
				<div>
					<label for="fullname">Name</label> <input type="text" id="fullname" name="fullname" />
				</div><div> 
					<label for="email">Email</label> <input type="text" id="email" name="email" />
				</div><div>  
					<label for="phone">Phone</label> <input type="text" id="phone" name="phone" />
				</div><div>  
					<label for="checkin">Checkin</label> 
					<input type="text" class="limiteddate" id="checkin" name="checkin" value="<?=date('d M Y',time())?>"/>
				</div><div>  
					<label for="checkout">Checkout</label> 
					<input type="text" class="limiteddate " id="checkout" name="checkout" value="<?=date('d M Y',time() + 86400)?>"/>
				</div><div> 
					<label for="payment">Payment</label><input type="number" id="payment" name="paymentdata[payed]" />		
				</div><div> 
					<label for="time">Arival time</label> 
					<select name='time'>
					<?php foreach (explode(',',$Vars['arivalTime']) as $opt)  echo "<option value='$opt'>$opt</option>";?>
					</select> 
				</div> 
				
				<label for="comment">Comment</label> <textarea id="comment" name="comment"></textarea><br />
				<h2>
					Days : <span id="newbookingDays">1</span><br />
					Total : $<span id="ptotal"></span><br />
					<?php if($Vars['discountCard'] != 'none'):?>
						<img src="<?=PLUGIN_ROOT_URL?>img/discountcards/<?=$Vars['discountCard']?>.png" 
						title="<?=__('bbh',PLUGIN_TRANS_NAMESPACE)?>" alt="<?=$Vars['discountCard']?>" />
						Total : $<span id="dtotal"></span>
					<?php endif; ?>
				</h2>
				<span id="dbox"></span>
				<h2><button class="saveButton" ><?=__('Make Booking',PLUGIN_TRANS_NAMESPACE)?></button></h2>
			</div>
			</form>
		</div>
		
		<div id="laundryDisplay"  class="hidden popupup-box">
			<div class="floatleft">
				<h2><?=__('Today',PLUGIN_TRANS_NAMESPACE)?> <?=date('m/d',strtotime('today'))?></h2>
				<?php $this->showLaundry(strtotime('today'))?>
			</div>		
			<div class="floatleft">
				<h2><?=__('Tomorrow',PLUGIN_TRANS_NAMESPACE)?> <?=date('m/d',strtotime('+1day'))?></h2>
				<?php $this->showLaundry(strtotime('+1day'))?>
			</div>
			<div class="floatleft">
				<h2><?=date('l m/d',strtotime('+2 days'))?></h2>
				<?php $this->showLaundry(strtotime('+2 days'))?>
			</div>
			<div class="floatleft">
				<h2><?=date('l m/d',strtotime('+3 days'))?></h2>
				<?php $this->showLaundry(strtotime('+3 days'))?>
			</div>
			<div class="clear"></div>
			<div class="floatright">
				<small class="warning"><?=__('checking out',PLUGIN_TRANS_NAMESPACE)?></small>		
				<small class="success"><?=__('checking in',PLUGIN_TRANS_NAMESPACE)?></small>
			</div>
		</div>
		
		
		<form action="" method="POST" id="makepaymentform"> 
			<input type="hidden" name="invoice"  class="invoice" value="" />
			<input type="hidden" name="payment"  class="payment" value="" />
			<input type="hidden" id="action" name="action" value="makepayment" />
		</form>
		
		<div id="calenderDisplay" class="popupup-box">	<?php  $checkinpage = true; include_once('calendar.php'); ?></div>

		<div id="occupacityDisplay" class="popupup-box"><?php 
			$oppacity = new occupacity();
			$oppacity->setup();
			$oppacity->setStartDate(date('Y-m-d',strtotime('today')));
			$oppacity->setEndDate(date('Y-m-d',strtotime('+7 days')));
			$oppacity->setVaribles('week');
			$oppacity->setStartDate(date('Y-m-d',strtotime('today')));
			$oppacity->setEndDate(date('Y-m-d',strtotime('+30 days')));
			$oppacity->setVaribles('month');
		?></div>
		<div class="clear"</div>
				
		<div id="checkinLimit">
			<a class="<?=$float?>" href="http://earthling.za.org"><img src="<?=PLUGIN_ROOT_URL?>img/icon-<?=$size?>.png"/></a>

			<h2>Showing bookings untill <?=date('l, dS F Y',strtotime($this->days.' days'))?>.</h2>		<div class="clear"</div>
		</div>
		<?php
	}
	
	private function showLaundry($day){
		global $wpdb;
		$sql=$wpdb->prepare('SELECT G.name, R.name as rental,B.beds,B.checkout,B.checkin
         FROM `'.$wpdb->prefix.DATABASE_PREFIX.'bookings` B 
				LEFT JOIN '.$wpdb->prefix.DATABASE_PREFIX.'roomtypes R ON B.idroomtype = R.id
				LEFT JOIN '.$wpdb->prefix.DATABASE_PREFIX.'guests G ON B.idguest = G.id 
         Where B.checkout = date(%s) OR B.checkin = date(%s)
			ORDER BY R.name,B.checkout',
			date('Y-m-d',$day),date('Y-m-d',$day)
		);
		
		foreach($wpdb->get_results( $sql) as $aDay) {
			?>
				<big><?php if($lastRental != $aDay->rental) echo $aDay->rental.'<br />';?></big>
				<small class="<?=($aDay->checkout == date('Y-m-d',$day) ? 'warning':'success') ?>">					
					<?=$aDay->name?> x <?=$aDay->beds?><br />
				</small>
			<?php 
			$lastRental = $aDay->rental; 
		}
	}
	/*the javascript css's and div's what go above and are used by interface */
	function echoDashboardSetup(){
		$this->includeScripts();
		$this-> includeCSSnDivs();
		$this->showFeedback();
		?>
		<script type="text/javascript"><!-- 
			var getRentalDetailsAjax = '<?=PLUGIN_ROOT_URL?>admin/ajax/getbookingDetails.php';	
		--></script>
		<h1 class="dashboardbuttons">
		<button class="occupacityButton" id="week">
			<img src="<?=PLUGIN_ROOT_URL?>/img/graphs-icon.png" alt="<?=__('Occupacity',PLUGIN_TRANS_NAMESPACE)?>" title="<?=__('Occupacity',PLUGIN_TRANS_NAMESPACE)?>" />
			<small><?=__('week',PLUGIN_TRANS_NAMESPACE)?></small>
		</button>
		<button class="occupacityButton" id="month">
			<img src="<?=PLUGIN_ROOT_URL?>/img/graphs-icon.png" alt="<?=__('Occupacity',PLUGIN_TRANS_NAMESPACE)?>" title="<?=__('Occupacity',PLUGIN_TRANS_NAMESPACE)?>" />
			<small><?=__('month',PLUGIN_TRANS_NAMESPACE)?></small>
		</button>
		<button id="calenderButton" >
			<img src="<?=PLUGIN_ROOT_URL?>/img/calender-icon.png" alt="<?=__('Calender',PLUGIN_TRANS_NAMESPACE)?>" title="<?=__('Calender',PLUGIN_TRANS_NAMESPACE)?>" />
			<small><?=__('calender',PLUGIN_TRANS_NAMESPACE)?></small>
		</button>
		<button id="laundryButton" >
			<img src="<?=PLUGIN_ROOT_URL?>/img/laundry.png" alt="<?=__('laundry',PLUGIN_TRANS_NAMESPACE)?>" title="<?=__('Calender',PLUGIN_TRANS_NAMESPACE)?>" />
			<small><?=__('laundry',PLUGIN_TRANS_NAMESPACE)?></small>
		</button>
		<button id="addNewBookingButton" >
			<img src="<?=PLUGIN_ROOT_URL?>/img/addbooking-icon.png" alt="<?=__('Add a Booking',PLUGIN_TRANS_NAMESPACE)?>" title="<?=__('Add a Booking',PLUGIN_TRANS_NAMESPACE)?>" />
			<small><?=__('booking',PLUGIN_TRANS_NAMESPACE)?></small>
		</button>
		</h1>
		<?php
	}
	
}
?>
