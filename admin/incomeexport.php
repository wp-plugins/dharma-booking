<?
/*currently unused */

global $wpdb;
require_once('../../../../wp-config.php');
require_once('functions.php');

require_once("Fusonic/bootstrapper.php") ;

include_once ('Fusonic\SpreadsheetExport\Spreadsheet');
include_once ('Fusonic\SpreadsheetExport\ColumnTypes\CurrencyColumn');
include_once ('Fusonic\SpreadsheetExport\ColumnTypes\DateColumn');
include_once ('Fusonic\SpreadsheetExport\ColumnTypes\NumericColumn');
include_once ('Fusonic\SpreadsheetExport\ColumnTypes\TextColumn');
include_once ('Fusonic\SpreadsheetExport\Writers\CsvWriter');
include_once ('Fusonic\SpreadsheetExport\Writers\TsvWriter');
include_once ('Fusonic\SpreadsheetExport\Writers\OdsWriter');

$bookings = array();

$roomtypes = getRoomtypes();
$startDate = (!empty($_GET['startDate'])?$_GET['startDate']:date('Y-m-d'));
$endDate = (!empty($_GET['endDate'])?$_GET['endDate']:date('Y-m-d',strtotime('+ 90days')));

$sql =
    'SELECT
        G.name,
        B.checkin,
        B.checkout,
        G.comment,
        R.name AS roomtype,
        R.id AS idroomtype,
        G.email,
        G.phone,
        R.capacity,
        B.beds,
        B.idguest,
        B.id AS bookingid,
				R.price,
        G.payment AS \'payment details\'
    FROM
				'.$wpdb->prefix.DATABASE_PREFIX.'bookings B
        LEFT JOIN '.$wpdb->prefix.DATABASE_PREFIX.'guests G ON G.id = B.idguest
        LEFT JOIN '.$wpdb->prefix.DATABASE_PREFIX.'roomtypes R ON B.idroomtype = R.id
    WHERE
		(B.checkin >= \''.date('y-m-d',strtotime($startDate)).'\' AND B.checkin <= \''.date('y-m-d',strtotime($endDate)).'\') '.($_GET['noadmin']?' AND G.id != 0':'').'
    GROUP BY
        G.id,
        B.checkin,
        R.name
    ORDER BY
        B.checkin ASC';

$res = mysql_query($sql);
while ($row = mysql_fetch_assoc($res)){
    $bookings[] = $row;
}

$export = new Spreadsheet();

// Add columns
$export->AddColumn(new TextColumn("Name"));
$export->AddColumn(new TextColumn("Email"));
$export->AddColumn(new TextColumn("Phone"));

$export->AddColumn(new DateColumn("Checkin"));
$export->AddColumn(new DateColumn("Checkout"));
$export->AddColumn(new NumericColumn("number of nights"));
$export->AddColumn(new TextColumn("Rental"));
$export->AddColumn(new NumericColumn("number of beds"));
$export->AddColumn(new TextColumn("comment"));

$bipCol = new CurrencyColumn("Price");
$bipCol->currency = CurrencyColumn::CURRENCY_USD;
$export->AddColumn($bipCol);


foreach ($bookings as $booking) { 
	$nights = findNonights($booking['checkin'],$booking['checkout']) ;
	$noNights =  $nights;
	$nights = $noNights.' night'.($noNights>1?'s':'');
	$priceTotal += $noNights*$booking['beds']*$booking['price'];
	
	$export->AddRow(array(ucFirst($booking['name']), 
										$booking['email'],
										$booking['phone'],
										$booking['checkin'],
										$booking['checkout'],
										$nights,
										$booking['roomtype'],
										$booking['beds'],
										$booking['comment'],
										($noNights*$booking['beds']*$booking['price'])));
} 

// Instantiate writer (CSV)
// $writer = new CsvWriter();
// $writer->charset = CsvWriter::CHARSET_ISO;

// Instantiate writer (TSV)
// $writer = new TsvWriter();
// $writer->charset = TsvWriter::CHARSET_ISO;

// Instantiate writer (ODS)
$writer = new OdsWriter();

// Save
// $export->Save($writer, "/tmp/Sample");

// Download
$export->Download($writer, date('d-M-Y',strtotime($startDate)));
?>

