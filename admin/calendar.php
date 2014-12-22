<?php
//require_once('dharmaAdmin.php');
/* this code is thanks to mataias i have changed it around a little but is almost all thats left of his code*/
function insertBooking ($idguest, $idroomtype, $beds, $dates, $invoice,$offsets = null ) { // offsets is reall post data array... 
 	global $wpdb;
    if(empty($offsets['time'])){
        $offsets[0] = intval($offsets[0]);
        $offsets[1] = intval($offsets[1]);        
        $sql = "INSERT INTO ".$wpdb->prefix.DATABASE_PREFIX."bookings (idguest, idroomtype, beds, checkin, checkout, invoice)
            VALUES ('$idguest', '$idroomtype', '$beds', ADDDATE('{$dates[0]}', {$offsets[0]}), ADDDATE('{$dates[1]}', {$offsets[1]}),$invoice)";
    }else{
			$time = $offsets['time'];
      $sql = "INSERT INTO ".$wpdb->prefix.DATABASE_PREFIX."bookings (idguest, idroomtype,beds, checkin, checkout, invoice,thetime,allbookingdata) VALUES ('$idguest', '$idroomtype', '$beds', '".date('Y-m-d',strtotime($dates[0]))."','".date('Y-m-d',strtotime($dates[1]))."',$invoice,'$time','".serialize($offsets)."')";
    }
    mysql_query($sql);
}

function getAvailab($from, $to, $totalAvailability = false) {
	global $wpdb;
	$sql = "SELECT R.id AS idroomtype, R.minimum, D.date, DATEDIFF(D.date, '$from') AS day_no, R.capacity, R.capacity - SUM(B.beds) AS availab
			FROM
				(SELECT
					checkin AS date FROM ".$wpdb->prefix.DATABASE_PREFIX."bookings
					UNION SELECT checkout FROM ".$wpdb->prefix.DATABASE_PREFIX."bookings
					UNION SELECT DATE('$from')) D
				/*without the next union, dates without bookings would be NULL*/
				LEFT JOIN (SELECT id, idguest, idroomtype, beds, checkin, checkout FROM ".$wpdb->prefix.DATABASE_PREFIX."bookings UNION
						SELECT 0 /*id*/, 0 /*idguest*/, id /*idroomtype*/, 0 /*beds*/,
						DATE('$from'), DATE('$to') FROM ".$wpdb->prefix.DATABASE_PREFIX."roomtypes) B
					ON D.date BETWEEN B.checkin AND ADDDATE(B.checkout, -1)
				LEFT JOIN ".$wpdb->prefix.DATABASE_PREFIX."roomtypes R
					ON B.idroomtype = R.id
			WHERE
				D.date BETWEEN '$from' AND ADDDATE('$to', -1)
				AND active	= 1
			GROUP BY	R.menuorder, D.date, B.idroomtype
			ORDER BY	R.menuorder";
	//this addition to the query is used for the ajax availability request, what i don't think is used anymore....
	if ($totalAvailability) $sql =	"SELECT idroomtype, minimum, MIN(availab) AS maximum FROM ($sql) Availabilities GROUP BY idroomtype, minimum";
	 	
	$calendar = array();
	foreach($wpdb->get_results($sql) as $d){
		if ($totalAvailability){ 
			$calendar[$d->idroomtype] = array(intval($d->minimum), intval($d->maximum));
		}else{
			$calendar[$d->idroomtype][$d->day_no] = intval($d->availab);
		}
	}

	if (!$totalAvailability) {
		$days = findNonights($from, $to);
		$newCal = array();
		foreach ($calendar as $j => $roomCal) {
			$newRoomCal = array();
			for ($i = 0; $i < $days; $i++) {
				if (isset($roomCal[$i]))
					$aux = $roomCal[$i];
				$newRoomCal[$i] = $aux;
			}
			$newCal[$j] = $newRoomCal;
		}
		$calendar = $newCal;
	}
	return $calendar;
}
function getRoomtypesTwo ($forSelect = false) {
  global $wpdb, $checkinpage;
	$roomtypes = array();
	$sql = 'SELECT id, name, minimum, capacity, price FROM '.$wpdb->prefix.DATABASE_PREFIX.'roomtypes ORDER by menuorder';

	//	while ($row = mysql_fetch_assoc($ as $d){res)) {
	foreach($wpdb->get_results($sql) as $d){
			if($checkinpage) $roomtypes[intval($d->id)]['display'] = $forSelect ? "{$d->name}" : $d;
			else						 $roomtypes[intval($d->id)]['display'] = $forSelect ? "{$d->name} ({$d->capacity})" : $d;
				
			$roomtypes[intval($d->id)]['capacity'] = $d->capacity;
			if (!$forSelect) unset($roomtypes[$d->id]['id']);
		}
		return $roomtypes;
}

$typoChecker = 1; //CHANGE THE 1 TO 0 TO STOP BEING ANNOYED BUT RISK MAKING MISTAKES! BWAHAHAHA!
global $wpdb, $checkinpage; 

if (!empty($_POST['availabChanges']) && !empty($_POST['changesDate'])) {
    $date = date('Y-m-d',strtotime($_POST['changesDate']));
    $rooms = json_decode(stripslashes($_POST['availabChanges']), true);
    if (empty($date) || empty($rooms)) {
        echo 'Something really bad happened related to the manual availability change. Sorry! Here are the responsible vars:<pre>';
        die(var_dump($_POST['availabChanges'], $_POST['changesDate'], $rooms));
    }
    foreach ($rooms as $idroomtype => $dates)
        foreach ($dates as $offset => $change)
            insertBooking(IDGUESTADMIN, $idroomtype, $change, array($date, $date), 0,array($offset, $offset + 1));
}

$from = !empty($_GET['startingDate']) ?  date('Y-m-d', strtotime($_GET['startingDate'])) :date('Y-m-d');
$days = 32;
$dateList = array();
$monthList = array();
$date = new DateTime($from);
$dayNameList = array('m','t','w','t','f','s','s');
for ($i = 0; $i < $days; $i++) {
    $dateList[] = $date->format('d');
    $dayList[] = $date->format('w');
    if (empty($monthList[$date->format('F')]))
        $monthList[$date->format('F')] = 1;
    else
        $monthList[$date->format('F')]++;
    $date->modify('+1 day');
    $to = $date->format('Y-m-d');
}
$calendar = getAvailab($from, $to);

$roomtypes = getRoomtypesTwo(true);

$url =  PLUGIN_ROOT_URL;

$dharmaAdmin = new dharmaAdmin();
$dharmaAdmin->includeCSSnDivs();
$dharmaAdmin->includeScripts();
?>
<?php if(!$checkinpage): ?> 
	<link type="text/css" href="<?=$url?>admin/styles.css" rel="stylesheet" />	
	<script type="text/javascript">
		var guests = <?=json_encode($guests)?>;
		var bookings = <?=json_encode($bookings)?>;
		var typoChecker = 1;
	<!--
	//moved to main script file after code rework
	jQuery(function () {
		jQuery("input.beds").click(function () { this.select(); })  .change(function () {
			jQuery(this).addClass("edited");
			var change = this.id.split("-");
			if (!(change[0] in changedRooms))
				changedRooms[change[0]] = {};
			changedRooms[change[0]][change[1]] = this.defaultValue - this.value;
			if (isNaN(this.value) || this.value < 0)
				alert("WARNING! entering '"+this.value+"' will produce an error.");
		});
	});
	-->
	</script>

	<form action="?page=caledar" method="GET">
		<h3>
			Viewing <?=$days?> days from  
			<input type="hidden" value="calendar" name="page" />
			<input type="text" onChange="this.form.submit();" class="datepicked" name="startingDate" id="startingDate" value="<?=date('d M Y',strtotime($from))?>" />
			<input type="submit" value="update" />
		</h3>
		
	</form>
<?php endif ?>
	
<table id="availab" class="spsheet" cellspacing="0">
    <thead>
        <tr> <th>&nbsp;</th> <?php foreach ($monthList as $month => $colspan) { ?> <th colspan="<?=$colspan?>"><?=$month?></th> <?php } ?> </tr>
        <tr> <th>&nbsp;</th> <?php foreach ($dateList as $date) : ?> <th class="date"><?=$date?></th> <?php endforeach ?> </tr>

    </thead>
    <tbody>
        <?php
        foreach ($calendar as $roomId => $dateAvailabilities) {
        ?>
            <tr>
                <th><?=$roomtypes[$roomId]['display']?></th>
                <?php
                foreach ($dateAvailabilities as $dayNo => $dateAvailability) : ?>
                    <?php
                    $class = '';
                    if($dateAvailability == 0){
                        $class = 'noavail';
                    }elseif($dateAvailability < 0) {
                        $class = 'negavail';
                    }else{
                    }
                    ?>
                    <td class="beds <?=$class?>">
								<?php if($checkinpage): ?> 
									<?=$dateAvailability?>
								<?php else: ?>  			 
									<input type="text" id="<?="$roomId-$dayNo"?>" class="beds <?=$class?>" value="<?=$dateAvailability?>"  />
								<?php endif ?>
                    </td>
                <?php endforeach ?>
            </tr>
        <?php } ?>
    </tbody>
	<thead> 
		<tr> <th>&nbsp;</th> <?php foreach ($dayList as $date) : ?> <th class="date"><?=$dayNameList[$date]?></th> <?php endforeach ?> </tr> 
	</thead>
</table>

<?php if(!$checkinpage): ?> 
	<form action="" method="POST" onSubmit="return saveChanges();">
		<input type="hidden" id="changesDate" name="changesDate" value="<?=$from?>" />
		<input type="hidden" id="availabChanges" name="availabChanges" />
		<h2><input type="submit" value="Save changes"  /></h2>
	</form>
	<h3>Make tempary changes to  avalibity here, note this does not change the number reported in a persons booking.</h3>
<?php endif ?>
