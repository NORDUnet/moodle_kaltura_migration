<?php
/*
 * Moodle Kaltura entry remapper 
 * Miun 2017
 * Version 0.8
 * 2017-12-14
 */

$BASEDIR = realpath(dirname(__file__));
if (is_readable($BASEDIR . "/config.php"))
	require_once($BASEDIR . "/config.php");
else
	die("Error reading config.php, See README.md for more instructions." . PHP_EOL);

if (is_readable($BASEDIR . "/kaltura_entrys.inc.php"))
	require_once($BASEDIR . "/kaltura_entrys.inc.php");
else
	die("Error reading kaltura_entrys.inc.php, See README.md for more instructions." . PHP_EOL);


include('kaltura_entrys.inc.php');
require_once('php5/KalturaClient.php');


$dbconn_m = mysqli_connect($MOODLE_DB_HOST, $MOODLE_DB_USER, $MOODLE_DB_PASSWORD) or die('Not connected to ' . $MOODLE_DB_HOST);
mysqli_select_db($dbconn_m, $MOODLE_DB_DATABASE) or die ('Cannot use db ' . $MOODLE_DB_DATABASE);
mysqli_query($dbconn_m, "SET NAMES 'utf8'");
//-------------------

$config = new KalturaConfiguration($KALTURA_PARTNER_ID);
$config->serviceUrl = $KALTURA_SERVICE_URL;
$client = new KalturaClient($config);
$ks = $client->generateSession($KALTURA_ADMIN_SECRET, $KALTURA_USER_ID, KalturaSessionType::ADMIN, $KALTURA_PARTNER_ID);
$client->setKs($ks);
//-------------------


//$searchService = new KalturaSearchService($client);
$filter = new KalturaMediaEntryFilter();
$pager = new KalturaFilterPager();
$pager->pageSize = 10;
$pager->pageIndex = 1;

//Filter entries
$filter = new KalturaMediaEntryFilter();
$filter->orderBy = KalturaMediaEntryOrderBy::CREATED_AT_DESC;
//$filter->mediaTypeEqual = 1;
//$filter->searchTextMatchAnd = 'keibry';
$idMap = array();

echo "(For safety reasons you should have a backup of your Moodle database)". PHP_EOL . PHP_EOL .
	"First step is to list all media entries found in the Kaltura instance" . PHP_EOL .
	"Would you like to proceed?(y/n)" . PHP_EOL;
if(!prompt())
	exit;
//Non optimised way of fetching all entries
//Fetch new IDs from Kaltura On-Prem
//New ID is found by searching for the old ID in <referenceid>
foreach($FILTERED_IDS as $oldId) {
	$filter->referenceIdEqual = $oldId;

	// get entries
	$result = $client->media->listAction($filter);
	foreach ($result->objects as $entry) {
        	if ($entry->mediaType == KalturaMediaType::VIDEO || $entry->mediaType == KalturaMediaType::AUDIO) {
			$idMap[$oldId] = $entry->id;
			echo $oldId . " => " . $entry->id . PHP_EOL;
		}
	}
}

print_r($idMap);

//First we have the most important update in the kalvidres-table
$mdl_kalvidres = 'mdl_kalvidres';
echo PHP_EOL . "---$mdl_kalvidres---" . PHP_EOL;
echo "Would you like to update all entry ids and source URLs in this table? (y/n)" . PHP_EOL;
if(prompt()) {
	foreach($idMap as $oldId => $newId) {
		//NOTE: this url replace won't take current player parameters into account
		//parameters are hard coded
		$kafSourceUrl = "http://kaltura-kaf-uri.com/browseandembed/index/media/entryid/".$newId.
		"/showDescription/true/showTitle/true/showTags/false/showDuration/true/showOwner/false/showUploadDate/false/playerSize/400x285/playerSkin/".$KALTURA_PLAYER_ID."/";
 	
		echo "Update " . $oldId . " => " . $newId . PHP_EOL; 

		$res_m = mysqli_query($dbconn_m, 
			"UPDATE mdl_kalvidres
			SET uiconf_id='1',
			source='".$kafSourceUrl."',
			entry_id='".$newId."'
			WHERE entry_id='".mysqli_real_escape_string($dbconn_m, $oldId)."'");

		if(mysqli_affected_rows($dbconn_m) < 1)
			echo "Failed - Old ID not found in moodle kalvidres table:" . $oldId; 
		else
			echo "Update successful";
		echo PHP_EOL . "-----" . PHP_EOL;
	}
}

//Several tables in moodle could need som entity replacement
//These tables follows below
$mdl_page = 'mdl_page';
echo PHP_EOL . "---$mdl_page---" . PHP_EOL;
echo "Would you like to replace all entity ids in this table? (y/n)" . PHP_EOL;
if(prompt()) {
	migrateIds($mdl_page, 'intro');
	migrateIds($mdl_page, 'content');
}
$mdl_course_sections = 'mdl_course_sections';
echo PHP_EOL . "---$mdl_course_sections---" . PHP_EOL;
echo "Would you like to replace all entity ids in this table? (y/n)" . PHP_EOL;
if(prompt()) {
	migrateIds($mdl_course_sections, 'summary');
}
$mdl_book = 'mdl_book';
echo PHP_EOL . "---$mdl_book---" . PHP_EOL;
echo "Would you like to replace all entity ids in this table? (y/n)" . PHP_EOL;
if(prompt()) {
	migrateIds($mdl_book, 'intro');
}
$mdl_book_chapters = 'mdl_book_chapters';
echo PHP_EOL . "---$mdl_book_chapters---" . PHP_EOL;
echo "Would you like to replace all entity ids in this table? (y/n)" . PHP_EOL;
if(prompt()) {
	migrateIds($mdl_book_chapters, 'title');
	migrateIds($mdl_book_chapters, 'content');
}

echo PHP_EOL . "Done!";
echo PHP_EOL . "Do not forget to purge caches, either through moodle interface or CLI." . PHP_EOL;


/*
 * Map all entity ids and player ids
 * $dbTable - table 
 * $dbColumn - column
*/
function migrateIds($dbTable, $dbColumn) {
	global $dbconn_m, $idMap, $oldPlayerIds, $KALTURA_PLAYER_ID;
	
	foreach($idMap as $oldId => $newId) {
		echo "Column $dbColumn: $oldId  => $newId" . PHP_EOL;
		$res_m = mysqli_query($dbconn_m, 
			"UPDATE $dbTable
			SET $dbColumn = REPLACE($dbColumn, 
			'$oldId', '$newId')");
		$aRows = mysqli_affected_rows($dbconn_m);
		if($aRows > 0)
			echo $oldId . " found in $dbColumn and replaced in $aRows row(s)" . PHP_EOL;

	}
	echo PHP_EOL . "-----" . PHP_EOL;

	echo "Now replacing old playerIds with " . $KALTURA_PLAYER_ID . PHP_EOL;
	foreach($oldPlayerIds as $oldPlayerId) {
		$res_m = mysqli_query($dbconn_m, 
			"UPDATE $dbTable
			SET $dbColumn = REPLACE($dbColumn,
			 '$oldPlayerId', '$KALTURA_PLAYER_ID')");
		$aRows = mysqli_affected_rows($dbconn_m);
		if($aRows > 0)
			echo $oldPlayerId . " replaced in $aRows row(s)" . PHP_EOL;
		else 
			echo $oldPlayerId . " not found" . PHP_EOL;
	}

	echo "-----" . PHP_EOL;
}

/*
 * Prompt for yes or no
 * returns true on y otherwise false
*/
function prompt() {
	$handle = fopen("php://stdin","r");
	$line = fgets($handle);
	$status = true;
	if(trim($line) != 'y'){
		echo "Operation aborted" . PHP_EOL;
		$status = false;
	}
	fclose($handle);
	return $status;
}

?>
