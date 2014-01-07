<?php 
/*
please add new reporting objects into reports folder 
*/

require_once('reports/occupacity.php');

class reports extends dharmaAdmin {
		function reports(){
		$this->setupVariables();
		$this->includeScripts();
		$this-> includeCSSnDivs();
		$this->doPostActions();
		$this->showFeedback();
	//	$this->exportLink();

		$this ->makeMenu(array('chart','spreadsheet','guests'), 'chart');		
		switch($_GET['section']){
			case 'guests':
				$this->guestsReport();
				break;
			case 'rentals':
				$this->bookingsReport();
				break;
			case 'spreadsheet':
				$this->incomeReport();
				break;
			default:
			case 'chart':
				$occupacity = new occupacity();
				$this->makeControls();
				$occupacity->setup();
				$occupacity->setStartDate($this->startDate);
				$occupacity->setEndDate($this->endDate);
				$occupacity->setVaribles('month');
				$occupacity->javascriptGo('month');
				
			
				break;
		}
	}
/* set up all valiables*/ 
	protected function setupVariables(){
		$this->startDate = (!empty($_GET['startDate'])?$_GET['startDate']:date('Y-m-d',strtotime('-1 days')));
		$this->endDate = (!empty($_GET['endDate'])?$_GET['endDate']:date('Y-m-d',strtotime('+ 90days')));
		$this->endDateSQL = date('Y-m-d',strtotime($this->endDate));
		$this->startDateSQL = date('Y-m-d',strtotime($this->startDate));
		
		$this->currentUrl = $_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"];
		$this->limit = intval(!empty($_GET['limit']) ? $_GET['limit'] : 50);
		$this->pagenumber = intval(!empty($_GET['pagenumber']) ? $_GET['pagenumber'] : 1);
		$this->orderby = ($_GET['orderby']?$_GET['orderby']:'B.id');
		$this->sqlStart = date('y-m-d',strtotime($this->startDate));
		$this->sqlEnd = date('y-m-d',strtotime($this->endDate));
		$this->url = PLUGIN_ROOT_URL;
		$this->vars = get_option('Dharma_Vars');
	
	}
	/*make pagation*/
	function makePageation($get, $pagenumber, $total, $limit){
		unset($get['pagenumber']);
		foreach($get as $k => $v) {$temp[] = $k.'='.$v;}
		$url =  $_SERVER["PHP_SELF"].'?'.implode('&amp;',$temp);
		
		$thisPage=1;
		for($i=0;$i<$total;$i+=$limit ){
			$pagation .=  '<li class="'.($pagenumber == $thisPage?'currentPage':'').'">'.
											'<a href="'.$url.'&amp;pagenumber='.$thisPage.'">'.$thisPage.'</a>'.
										'</li>';
			$thisPage++;
		}
		if($_GET['pagenumber'] >1) 
			$pagation .=  '<li><a href="'.$url.'&amp;pagenumber='.($_GET['pagenumber']-1).'">&lt;</a></li>';
		else 
			$pagation .=  '<li>&nbsp;&nbsp;</li>';
		if($_GET['pagenumber']*$this->limit < $total) 
			$pagation .=  '<li><a href="'.$url.'&amp;pagenumber='.($pagenumber+1).'">&gt;</a></li>';
		return $pagation ;
	}
	function showPageation($total){
		if($total > $this->limit) :		?>
			<ul id="pagation"><?=$this->makePageation($_GET, $this->pagenumber,$total,$this->limit)?></ul>
		<?php endif ?>
		Showing <?=(($this->pagenumber - 1) * $this->limit + 1)?> to <?=(($this->pagenumber * $this->limit) > $total?$total:($this->pagenumber * $this->limit))?> of <?=$total?> 
		<?php
		
	}
	protected function makeControls (){
		
		$startDate = date('d M Y',strtotime($this->startDate));
		$endDate  = date('d M Y',strtotime($this->endDate)); 
		?>
			<div id="control">
			<form action="" method="GET"  id="controlForm" class="floatleft">
				<input type="hidden" value="reports" name="page" />
				<input type="hidden" value="<?=$_GET['section']?>" name="section" />
				<label for="startDate">from</label>
				<input type="text"  onChange="this.form.submit();" name="startDate" id="startDate" value="<?=$startDate?>" />
				<label for="endDate">to</label>
				<input type="text"  onChange="this.form.submit();" name="endDate" id="endDate" value="<?=$endDate?>" />
			</form>
			<div class="clear"></div>
		</div>	
		<?php
	}
	function exportLink(){
		if($_GET['section'] == 'income'){
			?>
			<div style="float:right; padding-right:40px;">
				<a href="<?=$this->url?>admin/incomeexport.php?startDate=<?=$this->startDate?>&endDate=<?=$this->endDate?><?=($_GET['noadmin']?'&noadmin=on':'')?>">
				<img width="30px" src="<?=$this->url?>img/ods.png" title="export to ODS file" />
				</a>
			</div>
			<?php
		}
	}
	function includeJplot(){
		wp_enqueue_script('jplotmini',	PLUGIN_ROOT_URL.'libs/jplot/jquery.jqplot.min.js',											array('jquery'));
		wp_enqueue_script('jplotdate',	PLUGIN_ROOT_URL.'libs/jplot/plugins/jqplot.dateAxisRenderer.js',				array('jplotmini'));
		wp_enqueue_script('jplottext',	PLUGIN_ROOT_URL.'libs/jplot/plugins/jqplot.canvasTextRenderer.js',			array('jplotmini'));
		wp_enqueue_script('jplotcurs',	PLUGIN_ROOT_URL.'libs/jplot/plugins/jqplot.cursor.js',										array('jplotmini'));
		wp_enqueue_script('jplothi',		PLUGIN_ROOT_URL.'libs/jplot/plugins/jqplot.highlighter.js',								array('jplotmini'));
		wp_enqueue_script('jplottick',	PLUGIN_ROOT_URL.'libs/jplot/plugins/jqplot.canvasAxisTickRenderer.js',	array('jplotmini'));
		?>
		<link rel="stylesheet" type="text/css" href="<?=$this->url?>libs/jplot/jquery.jqplot.min.css" />
		<?php 
	}
	
/*
-------------------------------------------------------------------------------------------------------------------------
[display functions]
-------------------------------------------------------------------------------------------------------------------------
*/
	function incomeReport(){
		global $wpdb;
		$bookings = array();
		
		$url = plugins_url();
		$roomtypes = getRoomtypes(true);
	$sql ='
	SELECT
		G.name,        G.email,        G.phone,
      B.checkin,        B.checkout,        R.capacity,        B.beds,        B.idguest,        B.id AS bookingid,
      R.name AS roomtype,        R.id AS idroomtype,				R.price,
      I.payment, I.invoice, I.comment
   FROM '.$wpdb->prefix.DATABASE_PREFIX.'bookings B
      LEFT JOIN '.$wpdb->prefix.DATABASE_PREFIX.'guests G ON G.id = B.idguest
      LEFT JOIN '.$wpdb->prefix.DATABASE_PREFIX.'roomtypes R ON B.idroomtype = R.id
      LEFT JOIN '.$wpdb->prefix.DATABASE_PREFIX.'invoices I ON B.invoice = I.invoice   
   WHERE
		(B.checkin >= \''.$this->sqlStart.'\' 
		AND B.checkin <= \''.$this->sqlEnd.'\') AND G.id != 0'.
		($_GET['searchfield']?' AND (G.name LIKE \'%'.$_GET['searchfield'].'%\' 
																	OR G.email LIKE \'%'.$_GET['searchfield'].'%\'
																	OR G.phone LIKE \'%'.$_GET['searchfield'].'%\'
																	OR R.name LIKE \'%'.$_GET['searchfield'].'%\'
																	OR I.comment LIKE \'%'.$_GET['searchfield'].'%\'
																	)':'').'
	GROUP BY        G.id,        B.checkin,        R.name        
	ORDER BY B.checkin ASC';
	
//		$res = mysql_query($sql);
		$total = mysql_num_rows(mysql_query($sql));
		$bookings = $wpdb->get_results($sql.' LIMIT '.(($this->pagenumber - 1) * $this->limit).', '.$this->limit );
		
		$startDate = date('d M Y',strtotime($this->startDate));
		$endDate  = date('d M Y',strtotime($this->endDate)); 
		?>
		
		<div id="control">
			<form action="" method="GET"  id="controlForm" class="floatleft">
				<input type="hidden" value="reports" name="page" />
				<input type="hidden" value="<?=$_GET['section']?>" name="section" />
				<label for="startDate">from</label>
				<input type="text" onChange="this.form.submit();" name="startDate" id="startDate" value="<?=$startDate?>" />
				<label for="endDate">to</label>
				<input type="text" onChange="this.form.submit();" name="endDate" id="endDate" value="<?=$endDate?>" />
				<label for="limit">entries per page</label>
				<input onChange="this.form.submit();" type="text" style="width:40px" name="limit" id="limit" value="<?=$this->limit?>" />
				<label for="search">search:</label>
				<input onChange="this.form.submit();" type="text" name="searchfield" style="width:300px"  value="<?=$_GET['searchfield']?>" />
			</form>
			<div class="clear"></div>
		</div>
		
		<table class="simple-table" width="100%">
			<tr>
				<th style="min-width:100px">Name</th>
				<th style="min-width:100px">Contact</th>
				<th style="min-width:100px" colspan="2">Booking</th>
				<th>comment</th>
				<th>price</th>
				<th></th>
			</tr>
			<? 
			foreach ($bookings as $booking) { 
				$nights = findNonights($booking->checkin,$booking->checkout) ;
				$noNights =  $nights;
				$nights = $noNights.' night'.($noNights>1?'s':'');
				$priceTotal += $noNights*$booking->beds*$booking->price;
				?>
				<tr>
					<td><?=(!empty($booking->name)?ucFirst($booking->name):'admin entry') ?></td>
					<td><?=$booking->email?></br><?=$booking->phone?> </td>
					<td><?= date('d/m',strtotime($booking->checkin))?> <?=$nights?></td>
					<td><?=$booking->roomtype?> x <?=$booking->beds?></td>
					<td><?=$booking->comment?></td>
					<td>$<?=($noNights*$booking->beds*$booking->price)?></td>
				</tr>
			<? } ?>
			<tr>
				<th colspan="5" style="text-align:right">Total</th>
				<th>$<?=money_format('%.2n', $priceTotal)?></th>
			</tr>
		</table>
		<?php
		$this->showPageation($total);
	}

/* 
shows data focaused on guests 
*/
	function guestsReport(){
		global $wpdb;
		$sql = 'SELECT G.name as Gname, G.phone,G.email, G.id
					FROM `'.$wpdb->prefix.DATABASE_PREFIX.'bookings` B 
						LEFT JOIN '.$wpdb->prefix.DATABASE_PREFIX.'guests G ON B.idguest = G.id 
					Where G.id != 0 '.
						($_GET['searchfield']?' AND (G.name LIKE \'%'.$_GET['searchfield'].'%\' 
																	OR G.email LIKE \'%'.$_GET['searchfield'].'%\'
																	OR G.phone LIKE \'%'.$_GET['searchfield'].'%\'
																	)':'').'
					GROUP BY G.name,G.email';
			
		$total = mysql_num_rows(mysql_query($sql));
		$guests = $wpdb->get_results( $sql.' LIMIT '.(($this->pagenumber - 1) * $this->limit).', '.$this->limit );
		?>
		<script type="text/javascript"><!-- 
		var getRentalDetailsAjax = '<?=$this->url?>admin/ajax/getbookingDetails.php';	
		--></script>		
		<div id="control">
			<form action="" method="GET"  id="controlForm" class="floatleft">
				<input type="hidden" value="reports" name="page" />
				<input type="hidden" value="<?=$_GET['section']?>" name="section" />
				<label for="search">search:</label>
				<input onChange="this.form.submit();" type="text" name="searchfield" style="width:300px"  value="<?=$_GET['searchfield']?>" />
				<label for="limit">entries per page</label>
				<input onChange="this.form.submit();" type="text" style="width:40px" name="limit" id="limit" value="<?=$this->limit?>" />
			</form>
			<div class="clear"></div>
		</div>
		
		<table width="100%" id="bookingReportTable">
			<? foreach ($guests as $guest) :
				$totalRentals=0;
				$totalNights	=0;
				$totalBalance=0;
				?>        
				<tr>
					<td>
						<h1 class="alignleft"><?=$guest->Gname?></h1>
						<?=' <a href="mailto:'.$guest->Gname.'<'.$guest->email.'>">e-mail</a> '.$guest->phone;?>
						<?=$guestContact?> 
					</td>
				</tr>
				<tr>
					<td>
						<?php
						$guestid = ($guest->id == NULL ? 1 : $guest->id); 
						$sql = 'SELECT B.checkin,B.checkout, SUM(B.beds) AS people, B.bookingtime,I.invoice,I.totaldue, I.payment,B.checkedin	
										FROM `'.$wpdb->prefix.DATABASE_PREFIX.'bookings` B 
											LEFT JOIN '.$wpdb->prefix.DATABASE_PREFIX.'guests G ON B.idguest = G.id 
											LEFT JOIN '.$wpdb->prefix.DATABASE_PREFIX.'invoices I ON B.invoice = I.invoice 
										WHERE  G.id = '.$guestid.'
										GROUP BY I.invoice ASC'	;
						$bookings = $wpdb->get_results( $sql);
						foreach ($bookings as $booking):// = $wpdb->get_row($sql)):// as $checkins) :
							$noNights =  findNonights($booking->checkin,$booking->checkout);
							$nights = $noNights.' night'.($noNights>1?'s':'');
							$guests = $booking->people.' guest'.($booking->people>1?'s':'');
							$totalRentals += $booking->people;
							$totalNights += $noNights;
							$rentedItems = ''; 
							$itemsSql ='SELECT B.checkin,B.checkout,R.capacity,B.beds,R.name,R.price
														FROM '.$wpdb->prefix.DATABASE_PREFIX.'bookings B
															LEFT JOIN '.$wpdb->prefix.DATABASE_PREFIX.'roomtypes R ON B.idroomtype = R.id
															LEFT JOIN '.$wpdb->prefix.DATABASE_PREFIX.'invoices I ON B.invoice = I.invoice   
														WHERE I.invoice = '.$booking->invoice.'';
							$items = $wpdb->get_results( $itemsSql);
							foreach ($items as $item){ $rentedItems .= '<li>'.$item->beds.' x '.$item->name.'</li>';}
						
							$balance = $booking->payment - $booking->totaldue;
							$totalBalance += $balance;
							$balanceClass = ($balance<0 ?'fail':'success');
							?>
							<div class="alignleft smallBox">
								<div class="floatright innerbox">
									Total:<?=$booking->totaldue ?>
									Payed:<?=$booking->payment	?>
									<h3>Balance:<span class="<?=$balanceClass?>"><?=$balance?></span></h3>
									<?php if($booking->checkedin): ?><strong class="checkedin">checkedin</strong><?php endif ?>
									<button id="booking<?=$booking->invoice?>" class="detailsButton">Details</button>
									<input type ="hidden"  class="invoice" value="<?=$booking->invoice?>" />
								</div>
								<h3>
									<?=date('d/m/y',strtotime($booking->checkin))?>-<?=date('d/m/y',strtotime($booking->checkout))?>
									<br /> <?=$nights?>
								</h3>
								<ul> <?=$rentedItems?> </ul>
								<h3><?=$guests?></h3>
							</div>
						<?php endforeach ?>
							
				</td>
			</tr>
			<tr><th><span class="alignright">
				Total Nights: <?=$totalNights?> Total Guests : <?=$totalRentals?> 
				Total Balance: <span class="<?=($totalBalance<0 ?'fail':'success')?>"><?=$totalBalance?></span>
			</span></th></tr>
    <? endforeach ?>
	</table>

	<?php
		$this->showPageation($total);
	}
	/* the report centerd around bookings */
	function bookingsReport(){
		$lastyear  = $this->getAyear(date('Y',strtotime('-1 year')).'-01-01', date('Y',strtotime('-1 year')).'-12-31');
		$thisyear  = $this->getAyear( date('Y',time()).'-01-01', date('Y',time()).'-12-31');
		$nextyear = $this->getAyear(date('Y',strtotime('+1 year')).'-01-01', date('Y',strtotime('+1 year')).'-12-31');

		foreach($thisyear as $res){
			$rentals[$res->id]['data'] .= '[ \''.date('Y-m-d',strtotime($res->date)).'\' , '.$res->beds.' ],';
			$rentals[$res->id]['capacity'] = $res->capacity;
			$rentals[$res->id]['name'] = $res->name;
			$rentals[$res->id]['price'] = $res->price;
			$rentals[$res->id]['discount'] = $res->discount;
			$rentals[$res->id]['id'] = $res->id;
		}
		foreach($lastyear as $res){
			$rentals[$res->id]['last'] .= '[ \''.date('Y-',time()).date('m-d',strtotime($res->date)).'\','.$res->beds.' ],';
		}		
		foreach($nextyear as $res){
			$rentals[$res->id]['next'] .= '[ \''.date('Y-',time()).date('m-d',strtotime($res->date)).'\','.$res->beds.' ],';
		}
		$this->includeJplot();
		?>
		
		<script type="text/javascript"><!-- 
			var thedata = [];
			var lastyear = [];
			var nextyear = [];
			var theYears = ['<?=date('Y',time())?>','<?=date('Y',strtotime('-1 year'))?>','<?=date('Y',strtotime('+1 year'))?>'];
			-->
		</script>
		<strong> this display also suffers from the same problem of only showing when the guest checkedin</strong>
		<table width="100%" class="rentalschartTable">
		<?php 	foreach($rentals as $rental):?>
			<tr> 
			<th >
				<h2><?=$rental['name']?></h2>
				capacity: <?=$rental['capacity']?><br />
				price: $<?=$rental['price']?><br />
				discount: $<?=$rental['discount']?><br />
				<div id="<?=$rental['id']?>-info" class="pointinfo"></div>
			</th>
			<td>
				<div id="<?=$rental['id']?>-chart" style="height:200px"></div>
			<script type="text/javascript"><!-- 
				thedata[<?=$rental['id']?>] = [<?=$rental['data']?>];
				lastyear[<?=$rental['id']?>] = [<?=$rental['last']?>];
				nextyear[<?=$rental['id']?>] = [<?=$rental['next']?>];
				jQuery(document).ready(function(){
					makeRentalChart('<?=$rental['id']?>','<?=$rental['name']?>','<?=$rental['capacity']?>');
				});
			-->
			</script>
			</td>
			</tr>
		<?php endforeach ?>
		</table>
		<?php
	}
	function getAyear($from, $to){
		global $wpdb;
		$sql = "SELECT R.id, R.minimum,R.name,R.price,R.discount, D.date, R.capacity , SUM(B.beds) as beds,DATEDIFF(D.date, '$from') AS day_no
		FROM 
		/* all important dates (i.e. dates in which the availability changes). dates not present here keep the availability from the previous day. */ 
		(SELECT checkin AS date FROM ".$wpdb->prefix.DATABASE_PREFIX."bookings UNION SELECT checkout FROM ".$wpdb->prefix.DATABASE_PREFIX."bookings UNION SELECT DATE('2012-11-18')) D 
		/* without the next union, dates without bookings would be NULL */ 
		LEFT JOIN (SELECT id, idguest, idroomtype, beds, checkin, checkout FROM ".$wpdb->prefix.DATABASE_PREFIX."bookings 
			UNION SELECT 0 /*id*/, 0 /*idguest*/, id /*idroomtype*/, 0 /*beds*/, DATE('".$from."'), DATE('".$to."') 
				FROM ".$wpdb->prefix.DATABASE_PREFIX."roomtypes) B ON D.date BETWEEN B.checkin AND ADDDATE(B.checkout, -1) 
		LEFT JOIN ".$wpdb->prefix.DATABASE_PREFIX."roomtypes R ON B.idroomtype = R.id 
		WHERE D.date BETWEEN '".$from."' AND ADDDATE('".$to."', -1) AND active	= 1 
		GROUP BY R.menuorder, D.date, B.idroomtype 
		ORDER BY R.menuorder";
		return $wpdb->get_results($sql);
	}
}
