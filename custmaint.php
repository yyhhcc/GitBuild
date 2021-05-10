<?php
internal_init();
// Make all code    change global variables available locally
foreach($GLOBALS as $arraykey=>$arrayvalue){if($arraykey[0]!='_' && $arraykey!="GLOBALS")global $$arraykey;}

//	Program Name:		custmaint.php
//	Program Title:		Maintain customer master file
//	Created by:			ANDYFaf2
//	Task:	     		ISSUE358
//	Template family:	Idaho
//	Template name:		SQL Page at a time maintenance
//	Purpose:        	Maintain a database file using embedded SQL. Supports options for add, change, delete and display. 
//	Program Modifications: change to Jim. 

// this line turns on more php page information
// phpinfo();

// DB Connection code
require('/esdi/websmart/v7.2/include/xl_functions001.php');
global $db2conn, $path; 
 
// Determine which path we are running in, live or test
$path = getenv('REQUEST_URI');
$pathArray = split('/',$path);


//if($pathArray[1] == LIVE_PATH)
//{
//      $db2lib = "BCDILEPDN1";
//}
//else
//{
//    $db2lib = "BCDILEQUA1";
//}

//Set the $db2lib variable with the correct library name based on the
//the path currently in use in the URL.These values are set in the 
//xl_user_preferences.php file (LIVE_PATH = /live) and (TEST_PATH = /test)
//If you have files in other libraries you can put them into 
//variables here and use these variable to fully qualify your files, e.g. 
//$db2lib2 = "someotherlib";
switch ($pathArray[1])
{
	case LIVE_PATH:
      $db2lib = "BCDILEPDN1";
	break;	
	case TEST_PATH: 
      $db2lib = "BCDILEQUA1";
    break;	
    default:
      $db2lib = "XL_WEBDEMO";
}

//set your main library here before the connection is made
$options = array('i5_naming' => DB2_I5_NAMING_ON, 'i5_lib' => $db2lib);
$db2conn = xl_db2_connect($options);


if(!$db2conn)
{
	die('Could not connect to database: ' . db2_conn_error());
	exit;
}

global $CMNAME_filt;
global $CMCUST_filt;
global $CMCITY_filt;
// Global variables should be defined here
global $ww_rrn, $ww_ordby, $ww_orddir, $ww_page, $ww_nx, $ww_prevpage, $ww_nextpage, $ww_listsize, $ww_mode, $ww_whrclause, $ww_selstring, $ww_program_state, $ww_count;

// Set maximum list size to 5 for this program
$ww_listsize = 5;

// Initialize the previous page and count of records
$ww_prevpage = 0;
$ww_count = 0;

// Create random field to avoid caching
$rnd = rand(0, 99999); 

// retrieve the  last state of the list: order-by column and direction (ascend/descend). 
if(isset($_SESSION[$pf_scriptname]))
	$ww_program_state = $_SESSION[$pf_scriptname];
if (isset($ww_program_state['ww_orddir']))
	$ww_orddir = $ww_program_state['ww_orddir'];
if (isset($ww_program_state['ww_ordby']))
	$ww_ordby = $ww_program_state['ww_ordby'];
if (isset($ww_program_state['ww_page']))
	$ww_page = $ww_program_state['ww_page'];

if (isset($ww_program_state['CMNAME_filt']))
	$CMNAME_filt = $ww_program_state['CMNAME_filt'];
if (isset($ww_program_state['CMCUST_filt']))
	$CMCUST_filt = $ww_program_state['CMCUST_filt'];
if (isset($ww_program_state['CMCITY_filt']))
	$CMCITY_filt = $ww_program_state['CMCITY_filt'];
// Retrieve the rrn (if any)
if (isset($_REQUEST['rrn']))
	$ww_rrn = $_REQUEST['rrn'];
// run the specified task
switch($pf_task)
{
	case 'default':
	display();
	break;
	
	// Record display option
	case 'disp':
	disprcd();
	break;
	
	// Delete confirm
	case 'delconf':
	disprcd();
	break;
	
	// Actual record deletion
	case 'del':
	deletercd();
	break;
	
	// Start the add or change process
	case 'beginmanage':
	beginmanage();
	break;
	
	// Complete the add or change process
	case 'endmanage':
	endmanage();
	break;    
	
	case '':
	filter();
	break;
}

//Release the database resource
db2_close($db2conn);
/********************
 End of mainline code
 ********************/


// Load page with filters
function filter()
{
	// Make all global variables available here
	foreach($GLOBALS as $arraykey=>$arrayvalue) 
	{
		if ($arraykey[0]!='_' && $arraykey != "GLOBALS")
			global $$arraykey;
	}
	
	// get the filters from request 
	
	$CMNAME_filt = xl_quote_string(trim($_REQUEST['CMNAME_filt']));
	$CMCUST_filt = xl_quote_string(trim($_REQUEST['CMCUST_filt']));
	$CMCITY_filt = xl_quote_string(trim($_REQUEST['CMCITY_filt']));
	
	// save filter into session 
	$ww_program_state['CMNAME_filt'] = $CMNAME_filt;
	$ww_program_state['CMCUST_filt'] = $CMCUST_filt;
	$ww_program_state['CMCITY_filt'] = $CMCITY_filt;
	display();
}
// Load first page and use ordby parameter from form to determine new sort order, direction 
function display()
{
	// Make all global variables available here
	foreach($GLOBALS as $arraykey => $arrayvalue) 
	{
		if ($arraykey[0]!='_' && $arraykey != 'GLOBALS')
			global $$arraykey;
	}
	
	
	// ensure that filtering criteria are valid. Also guards against SQL Injection attacks
	$CMNAME_filt = str_replace("'", "_", $CMNAME_filt);
	if($CMCUST_filt <> "" and (!is_numeric($CMCUST_filt)))
		$CMCUST_filt = "";
	$CMCITY_filt = str_replace("'", "_", $CMCITY_filt);
	
	
	// Retrieve or set the page to list
	if (isset($_REQUEST['page']))
		$pagenum = (int)$_REQUEST['page'];
	else 
		$pagenum = 1;
	
	// Calculate next and previous page number
	$ww_prevpage = $pagenum - 1;
	$ww_nextpage = $pagenum + 1;
	
	// Compute table row cursor offset, offset starts at 0
	$ww_nx = $pagenum * $ww_listsize;
	
	// Build select string for SQL exec
	bldselstr();    
	
	// Store the last used order-by settings: 
	$ww_program_state['ww_orddir'] = $ww_orddir;
	$ww_program_state['ww_ordby'] = $ww_ordby;
	$ww_program_state['ww_page'] = $pagenum;
	$_SESSION[$pf_scriptname] = $ww_program_state;
	
	// Build first page of table rows
	bldpage();    
}

// Build current page of rows up to listsize. 
function bldpage()
{
	// Make all global variables available here
	foreach($GLOBALS as $arraykey=>$arrayvalue) 
	{
		if ($arraykey[0]!='_' && $arraykey != "GLOBALS")
			global $$arraykey;
	}
	
	// Output page and list header
	wrtseg('ListHeader');
	
	// Fetch rows for page: relative to initial cursor 
	$ww_selstring = $ww_selstring." FETCH FIRST $ww_nx ROWS ONLY";
	if (!($stmt = db2_exec($db2conn, $ww_selstring, array('CURSOR' => DB2_SCROLLABLE)))) 
	{
		die("<b>Error ".db2_stmt_error() .":".db2_stmt_errormsg(). "</b>"); 
	}
	
	// While SQL retrieves records and show them
	$index = $ww_nx - $ww_listsize + 1;
	while ($row = db2_fetch_assoc($stmt, $index))
	{
		// set color of the line
		xl_set_row_color('altcol1', 'altcol2');
		
		$ww_rrn = $row['00001'];
		
		// Get the fields 
		$CMNAME = $row['CMNAME'];
		$CMCUST = $row['CMCUST'];
		$CMADR1 = $row['CMADR1'];
		$CMADR2 = $row['CMADR2'];
		$CMCITY = $row['CMCITY'];
		$CMSTATE = $row['CMSTATE'];
		$CMCOUNT = $row['CMCOUNT'];
		$CMPOST = $row['CMPOST'];
		$CMCONT = $row['CMCONT'];
		$CMDACR = $row['CMDACR'];
		
		wrtseg('ListDetails');
		$index++;
	}
	
	// test for more records
	$ww_count = $index - ($ww_nx - $ww_listsize) - 1;                               
	
	// show the footer
	wrtseg('ListFooter');  
	
	// close the database connection
	db2_close($db2conn);   
}

// Build SQL Select string: 
function bldselstr()
{
	// Make all global variables available here
	foreach($GLOBALS as $arraykey => $arrayvalue) 
	{
		if ($arraykey[0]!='_' && $arraykey != 'GLOBALS')
			global $$arraykey;
	}

//  add MU_CUSTF.CMPOST to selected field list	
//	$ww_selstring = 'SELECT RRN(MU_CUSTF), MU_CUSTF.CMNAME, MU_CUSTF.CMCUST, MU_CUSTF.CMADR1, MU_CUSTF.CMADR2, MU_CUSTF.CMCITY, MU_CUSTF.CMSTATE, MU_CUSTF.CMCOUNT, MU_CUSTF.CMCONT, MU_CUSTF.CMDACR FROM XL_WEBDEMO/MU_CUSTF'; 
	$ww_selstring = "SELECT RRN(MU_CUSTF), MU_CUSTF.CMNAME, MU_CUSTF.CMCUST, MU_CUSTF.CMADR1, MU_CUSTF.CMADR2, MU_CUSTF.CMCITY, MU_CUSTF.CMSTATE, MU_CUSTF.CMCOUNT, MU_CUSTF.CMPOST, MU_CUSTF.CMCONT, MU_CUSTF.CMDACR FROM $db2lib/MU_CUSTF"; 
	
	/**** Build 'where' clause ****/
	
	$ww_whrclause = '';
	$whrlink = " where";
	
	// filter by CMNAME
	if ($CMNAME_filt <> '')
	{
		$ww_whrclause = trim($ww_whrclause) . $whrlink . ' MU_CUSTF.CMNAME = ' . "'" . trim($CMNAME_filt) . "'";
		$whrlink = " and";
	}
	
	// filter by CMCUST
	if ($CMCUST_filt <> '')
	{
		$ww_whrclause = trim($ww_whrclause) . $whrlink . ' MU_CUSTF.CMCUST = ' . "'" . trim($CMCUST_filt) . "'";
		$whrlink = " and";
	}
	
	// filter by CMCITY
	if ($CMCITY_filt <> '')
	{
		$ww_whrclause = trim($ww_whrclause) . $whrlink . ' MU_CUSTF.CMCITY = ' . "'" . trim($CMCITY_filt) . "'";
		$whrlink = " and";
	}
	
	$ww_selstring = trim($ww_selstring) . ' ' . $ww_whrclause;
	
	/**** Build 'order-by' clause ****/
	
	// If a column header was clicked, set the order by field to 
	// the correct column and control ascending and descending order
	// Check for a sort request 
	if (isset($_REQUEST['ordby']))
	{
		$ordby = $_REQUEST['ordby'];  
		
		// If we previously sorted on this column, then reverse the order of the sort: 
		if ($ordby == $ww_ordby) 
		{
			if ($ww_orddir == 'A') 
				$ww_orddir = 'D';
			else 
				$ww_orddir = 'A';
		}       
		else
		{
			// Save last used column for sort.
			$ww_ordby = $ordby;           
			
			// Ascending order
			$ww_orddir ='A';    
		}
	}
	
	// If a sort-by column exists then use that to build the order-by
	if ($ww_ordby <> "")
	{
		$ww_selstring = trim($ww_selstring) . ' order by ' . $ww_ordby; 
		
		// If descending order: 
		if ($ww_orddir == 'D') 
			$ww_selstring = trim($ww_selstring) . ' DESC'; 
	}
	else 
	{
		// Otherwise just use the default order by
		$ww_selstring = trim($ww_selstring) . ' order by CMCUST ';
	}
}                         

// Display details for selected record:
function disprcd() 
{
	// Make all global variables available here
	foreach($GLOBALS as $arraykey=>$arrayvalue) 
	{
		if ($arraykey[0]!='_' && $arraykey != "GLOBALS")
			global $$arraykey;
	}
	
	// Fetch the row for page
	//$sqlstr = 'SELECT  CMCUST, CMNAME, CMADR1, CMADR2, CMCITY, CMSTATE, CMCOUNT, CMPOST, CMAREA, CMPHON, CMCONT, CMEMAIL, CMTERM, CMDACR, CMDSCNT FROM XL_WEBDEMO/MU_CUSTF WHERE RRN(MU_CUSTF) = ' . $ww_rrn;
	$sqlstr = "SELECT  CMCUST, CMNAME, CMADR1, CMADR2, CMCITY, CMSTATE, CMCOUNT, CMPOST, CMAREA, CMPHON, CMCONT, CMEMAIL, CMTERM, CMDACR, CMDSCNT FROM $db2lib/MU_CUSTF WHERE RRN(MU_CUSTF) = " . $ww_rrn;
	if (!($result = db2_exec($db2conn, $sqlstr))) 
	{
		// Release the database resource
		db2_close($db2conn);
		
		die("<b>Error ".db2_stmt_error().":".db2_stmt_errormsg()."</b>"); 
	}
	
	// put the result into global variable and show it    
	$row = db2_fetch_assoc($result);
	
	// Get fields 
	$CMCUST = $row['CMCUST'];
	$CMNAME = $row['CMNAME'];
	$CMADR1 = $row['CMADR1'];
	$CMADR2 = $row['CMADR2'];
	$CMCITY = $row['CMCITY'];
	$CMSTATE = $row['CMSTATE'];
	$CMCOUNT = $row['CMCOUNT'];
	$CMPOST = $row['CMPOST'];
	$CMAREA = $row['CMAREA'];
	$CMPHON = $row['CMPHON'];
	$CMCONT = $row['CMCONT'];
	$CMEMAIL = $row['CMEMAIL'];
	$CMTERM = $row['CMTERM'];
	$CMDACR = $row['CMDACR'];
	$CMDSCNT = $row['CMDSCNT'];
	
	
	// output the segment
	wrtseg('rcddisplay');
}

// Delete the record
function deletercd() 
{
	// Make all global variables available here
	foreach($GLOBALS as $arraykey=>$arrayvalue) 
	{ 
		if ($arraykey[0]!='_' && $arraykey != "GLOBALS")
			global $$arraykey;
	}
	
	// delete the record and avoid deleting the whole table
	//$result = db2_exec($db2conn, 'delete from XL_WEBDEMO/MU_CUSTF where rrn(MU_CUSTF)= ' . $ww_rrn);
	$result = db2_exec($db2conn, "delete from $db2lib/MU_CUSTF where rrn(MU_CUSTF)= " . $ww_rrn);
	if (!$result)
		die("Error" .db2_stmt_error() . ":" . db2_stmt_errormsg());
	
	// Release the database resource
	db2_close($db2conn);
	
	// Redirect to display page
	header("Location: $pf_scriptname?page=" . (string)$ww_page);
}

// Present panel to prepare to Add or Change records:
function beginmanage() 
{
	// Make all global variables available here
	foreach($GLOBALS as $arraykey=>$arrayvalue) 
	{
		if ($arraykey[0]!='_' && $arraykey != "GLOBALS")
			global $$arraykey;
	}
	
	//$sqlstatement = 'SELECT  CMCUST  , CMNAME, CMADR1, CMADR2, CMCITY, CMSTATE, CMCOUNT, CMPOST, CMAREA, CMPHON, CMCONT, CMEMAIL, CMTERM, CMDACR, CMDSCNT FROM XL_WEBDEMO/MU_CUSTF ';
	$sqlstatement = "SELECT  CMCUST  , CMNAME, CMADR1, CMADR2, CMCITY, CMSTATE, CMCOUNT, CMPOST, CMAREA, CMPHON, CMCONT, CMEMAIL, CMTERM, CMDACR, CMDSCNT FROM $db2lib/MU_CUSTF ";
	$condition = 'WHERE rrn(MU_CUSTF)= ' . $ww_rrn;
	$ww_mode = $_REQUEST['mode'];
	
	// if the mode is change, get the record from the database
	if ($ww_mode == 'Change')
	{
		$sqlstatement .= $condition;
		
		// Fetch the row for page
		if (!($result = db2_exec($db2conn, $sqlstatement, array('CURSOR' => DB2_SCROLLABLE)))) 
		{
			die("<b>Error ". db2_stmt_error().":" . db2_stmt_errormsg()."</b>"); 
		}
		
		// put the result into global variable and show it    
		$row = db2_fetch_assoc($result);
		
		// Get the fields 
		$CMCUST = rtrim($row['CMCUST']);
		$CMNAME = rtrim($row['CMNAME']);
		$CMADR1 = rtrim($row['CMADR1']);
		$CMADR2 = rtrim($row['CMADR2']);
		$CMCITY = rtrim($row['CMCITY']);
		$CMSTATE = rtrim($row['CMSTATE']);
		$CMCOUNT = rtrim($row['CMCOUNT']);
		$CMPOST = rtrim($row['CMPOST']);
		$CMAREA = rtrim($row['CMAREA']);
		$CMPHON = rtrim($row['CMPHON']);
		$CMCONT = rtrim($row['CMCONT']);
		$CMEMAIL = rtrim($row['CMEMAIL']);
		$CMTERM = rtrim($row['CMTERM']);
		$CMDACR = rtrim($row['CMDACR']);
		$CMDSCNT = rtrim($row['CMDSCNT']);
		
		db2_close($db2conn);
	}
	else 
	{
		// Initialize fields for add here:
		
		$ww_rrn=0;
	}
	
	wrtseg('RcdManage');
}

// Accept user input and pass to add or change record
function endmanage() 
{
	// Make all global variables available here
	foreach($GLOBALS as $arraykey=>$arrayvalue) 
	{
		if ($arraykey[0]!='_' && $arraykey != "GLOBALS")
			global $$arraykey;
	}
	
	$ww_mode = $_REQUEST['mode'];
	
	// get values from the page 
	$CMCUST = $_REQUEST["CMCUST"];
	if($CMCUST == '')
		$CMCUST = 0;
	$CMNAME = $_REQUEST["CMNAME"];
	$CMADR1 = $_REQUEST["CMADR1"];
	$CMADR2 = $_REQUEST["CMADR2"];
	$CMCITY = $_REQUEST["CMCITY"];
	$CMSTATE = $_REQUEST["CMSTATE"];
	$CMCOUNT = $_REQUEST["CMCOUNT"];
	$CMPOST = $_REQUEST["CMPOST"];
	$CMAREA = $_REQUEST["CMAREA"];
	if($CMAREA == '')
		$CMAREA = 0;
	$CMPHON = $_REQUEST["CMPHON"];
	if($CMPHON == '')
		$CMPHON = 0;
	$CMCONT = $_REQUEST["CMCONT"];
	$CMEMAIL = $_REQUEST["CMEMAIL"];
	$CMTERM = $_REQUEST["CMTERM"];
	if($CMTERM == '')
		$CMTERM = 0;
	$CMDACR =	$_REQUEST["CMDACR"];
	if($CMDACR == '')
		$CMDACR = '0001-01-01';
	$CMDSCNT = $_REQUEST["CMDSCNT"];
	if($CMDSCNT == '')
		$CMDSCNT = 0;
	
	
	if ($ww_mode == 'Add')
	{
		// do any add validation here
		
		// Add row to table: 
		//$result = db2_exec($db2conn, 'INSERT INTO XL_WEBDEMO/MU_CUSTF ( CMCUST, CMNAME, CMADR1, CMADR2, CMCITY, CMSTATE, CMCOUNT, CMPOST, CMAREA, CMPHON, CMCONT, CMEMAIL, CMTERM, CMDACR, CMDSCNT) VALUES(' .  "'". xl_quote_string($CMCUST) . "'". ", '". xl_quote_string($CMNAME) . "'". ", '". xl_quote_string($CMADR1) . "'". ", '". xl_quote_string($CMADR2) . "'". ", '". xl_quote_string($CMCITY) . "'". ", '". xl_quote_string($CMSTATE) . "'". ", '". xl_quote_string($CMCOUNT) . "'". ", '". xl_quote_string($CMPOST) . "'". ", '". xl_quote_string($CMAREA) . "'". ", '". xl_quote_string($CMPHON) . "'". ", '". xl_quote_string($CMCONT) . "'". ", '". xl_quote_string($CMEMAIL) . "'". ", '". xl_quote_string($CMTERM) . "'". ", '". xl_quote_string($CMDACR) . "'". ", '". xl_quote_string($CMDSCNT) . "'" .") with NC");
		$result = db2_exec($db2conn, "INSERT INTO $db2lib/MU_CUSTF ( CMCUST, CMNAME, CMADR1, CMADR2, CMCITY, CMSTATE, CMCOUNT, CMPOST, CMAREA, CMPHON, CMCONT, CMEMAIL, CMTERM, CMDACR, CMDSCNT) VALUES(" .  "'". xl_quote_string($CMCUST) . "'". ", '". xl_quote_string($CMNAME) . "'". ", '". xl_quote_string($CMADR1) . "'". ", '". xl_quote_string($CMADR2) . "'". ", '". xl_quote_string($CMCITY) . "'". ", '". xl_quote_string($CMSTATE) . "'". ", '". xl_quote_string($CMCOUNT) . "'". ", '". xl_quote_string($CMPOST) . "'". ", '". xl_quote_string($CMAREA) . "'". ", '". xl_quote_string($CMPHON) . "'". ", '". xl_quote_string($CMCONT) . "'". ", '". xl_quote_string($CMEMAIL) . "'". ", '". xl_quote_string($CMTERM) . "'". ", '". xl_quote_string($CMDACR) . "'". ", '". xl_quote_string($CMDSCNT) . "'" .") with NC");
		
		// error handling
		if (!$result) 
		{
			// error handling code here
			die("<b>Error ". db2_stmt_error().":" . db2_stmt_errormsg()."</b>"); 
		}
	}
	
	
	if ($ww_mode == 'Change')
	{
		// do any change validation here
		
		// Update row in table. 
		//$result = db2_exec($db2conn, 'UPDATE XL_WEBDEMO/MU_CUSTF SET( CMCUST, CMNAME, CMADR1, CMADR2, CMCITY, CMSTATE, CMCOUNT, CMPOST, CMAREA, CMPHON, CMCONT, CMEMAIL, CMTERM, CMDACR, CMDSCNT) = (' .  "'". xl_quote_string($CMCUST) . "'". ", '" . xl_quote_string($CMNAME) . "'". ", '" . xl_quote_string($CMADR1) . "'". ", '" . xl_quote_string($CMADR2) . "'". ", '" . xl_quote_string($CMCITY) . "'". ", '" . xl_quote_string($CMSTATE) . "'". ", '" . xl_quote_string($CMCOUNT) . "'". ", '" . xl_quote_string($CMPOST) . "'". ", '" . xl_quote_string($CMAREA) . "'". ", '" . xl_quote_string($CMPHON) . "'". ", '" . xl_quote_string($CMCONT) . "'". ", '" . xl_quote_string($CMEMAIL) . "'". ", '" . xl_quote_string($CMTERM) . "'". ", '" . xl_quote_string($CMDACR) . "'". ", '" . xl_quote_string($CMDSCNT) . "'" .") where rrn(MU_CUSTF) = $ww_rrn with NC"); 
		$result = db2_exec($db2conn, "UPDATE $db2lib/MU_CUSTF SET( CMCUST, CMNAME, CMADR1, CMADR2, CMCITY, CMSTATE, CMCOUNT, CMPOST, CMAREA, CMPHON, CMCONT, CMEMAIL, CMTERM, CMDACR, CMDSCNT) = (" .  "'". xl_quote_string($CMCUST) . "'". ", '" . xl_quote_string($CMNAME) . "'". ", '" . xl_quote_string($CMADR1) . "'". ", '" . xl_quote_string($CMADR2) . "'". ", '" . xl_quote_string($CMCITY) . "'". ", '" . xl_quote_string($CMSTATE) . "'". ", '" . xl_quote_string($CMCOUNT) . "'". ", '" . xl_quote_string($CMPOST) . "'". ", '" . xl_quote_string($CMAREA) . "'". ", '" . xl_quote_string($CMPHON) . "'". ", '" . xl_quote_string($CMCONT) . "'". ", '" . xl_quote_string($CMEMAIL) . "'". ", '" . xl_quote_string($CMTERM) . "'". ", '" . xl_quote_string($CMDACR) . "'". ", '" . xl_quote_string($CMDSCNT) . "'" .") where rrn(MU_CUSTF) = $ww_rrn with NC"); 
		
		// error handling
		if (!$result) 
		{
			// error handling code here
			die("<b>Error ". db2_stmt_error().":" . db2_stmt_errormsg()."</b>"); 
		}
	}
	
	
	//Release the database resource
	db2_close($db2conn);
	
	//Redirect to display page
	header("Location: $pf_scriptname?page=" . (string)$ww_page);
}



function wrtseg($segment)
{
	// Make sure it's case insensitive
	$segment = strtolower($segment);

	// Make all global variables available locally
	foreach($GLOBALS as $arraykey=>$arrayvalue) {if($arraykey[0]!='_' && $arraykey != "GLOBALS")global $$arraykey;}

	// Output the requested segment:

	if($segment == "listheader")
	{

		echo <<<SEGDTA
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
  <head>
    <title>Bill's Music Shop</title>
    
    <meta content="WebSmart" name="generator" />
    <meta http-equiv="Pragma" content="no-cache" />
    <link media="screen, tv, projection" href="/websmart/v7.2/idaho/css/screen.css" type="text/css" rel="stylesheet" />
    <link media="print" href="/websmart/v7.2/idaho/css/print.css" type="text/css" rel="stylesheet" />
    <script src="/websmart/v7.2/javascript/esdiapi010.js" type="text/javascript">
	
	
	
	</script>
    <script type="text/javascript">
	
	xl_AttachEvent(window, "load", WindowInit); 

		function WindowInit()
		{
			xl_FocusFirstElement();
		}
	
	</script>
  </head>
  
  <body>
    <div id="headline">
      <div id="logo" title="Logo">&nbsp;</div>
      <div id="title">Maintain customers</div></div>
    <div id="divider">&nbsp;</div>
    <div id="contents">
      <!--
		Table containing filter inputs
		-->
      
      <form action="$pf_scriptname" method="post">
        <input type="hidden" value="filter" name="task" /> 
        <table class="keys">
          <tbody>
          <tr>
            <td>Customer Name</td>
            <td>
              <input value="$CMNAME_filt" name="CMNAME_filt" /></td>
          </tr>
          <tr>
            <td>Customer Number</td>
            <td>
              <input value="$CMCUST_filt" name="CMCUST_filt" /></td>
          </tr>
          <tr>
            <td>City</td>
            <td>
              <input value="$CMCITY_filt" name="CMCITY_filt" /></td>
          </tr>
          <tr>
            <td>&nbsp;</td>
            <td>
              <input type="submit" value="Filter" /></td>
          </tr>
          </tbody>
        </table>
      </form>
      <!--
		End table containing filter inputs
		-->
      
      <div id="listtopcontrol"><a class="nondisp" id="prevlinktop">Previous 
        $ww_listsize</a><a class="addlink" href="$pf_scriptname?task=beginmanage&mode=Add&rnd=$rnd">Add Record</a> <a class="nondisp" id="nextlinktop">Next 
        $ww_listsize</a></div>
      <table class="mainlist">
        <tbody>
        <tr>
          <th>Action</th>
          <th width="300"><a href="$pf_scriptname?ordby=CMNAME&rnd=$rnd">Customer Name</a></th>
          <th width="70"><a href="$pf_scriptname?ordby=CMCUST&rnd=$rnd">Customer Number</a></th>
          <th width="300"><a href="$pf_scriptname?ordby=CMADR1&rnd=$rnd">Address 1</a></th>
          <th width="300"><a href="$pf_scriptname?ordby=CMADR2&rnd=$rnd">Address 2</a></th>
          <th width="200"><a href="$pf_scriptname?ordby=CMCITY&rnd=$rnd">City</a></th>
          <th width="20"><a href="$pf_scriptname?ordby=CMSTATE&rnd=$rnd">State/Prov</a></th>
          <th width="20"><a href="$pf_scriptname?ordby=CMCOUNT&rnd=$rnd">Country</a></th>
          <th width="20"><a href="$pf_scriptname?ordby=CMPOST&rnd=$rnd">Postal Code</a></th>				
          <th width="500"><a href="$pf_scriptname?ordby=CMCONT&rnd=$rnd">Contact Name</a></th>
          <th width="100"><a href="$pf_scriptname?ordby=CMDACR&rnd=$rnd">Customer Since</a></th>
        </tr>
        
        
SEGDTA;
		return;
	}
	if($segment == "listdetails")
	{

		echo <<<SEGDTA

				<tr class="$pf_altrowclr">
					<td>
						<table class="actions">
							<tbody>
								<tr>
									<td><a href="$pf_scriptname?task=disp&rrn=$ww_rrn&rnd=$rnd">
											<img title="Display" alt="Display" src="/websmart/v7.2/idaho/images/view.gif" /></a></td>
									<td><a href="$pf_scriptname?task=beginmanage&mode=Change&rrn=$ww_rrn&rnd=$rnd">
											<img title="Edit" alt="Edit" src="/websmart/v7.2/idaho/images/edit.gif" /></a></td>
									<td><a href="$pf_scriptname?task=delconf&rrn=$ww_rrn">
											<img title="Delete" alt="Delete" src="/websmart/v7.2/idaho/images/delete.gif" /></a></td>
								</tr>
							</tbody>
						</table></td>
					<td>
						$CMNAME</td>
					<td>
						$CMCUST</td>
					<td>
						$CMADR1</td>
					<td>
						$CMADR2</td>
					<td>
						$CMCITY</td>
					<td>
						$CMSTATE</td>
					<td>
						$CMCOUNT</td>
					<td class="text">$CMPOST</td>
					<td>
						$CMCONT</td>
					<td>
						$CMDACR</td>
				</tr>
				
					
SEGDTA;
		return;
	}
	if($segment == "listfooter")
	{

		echo <<<SEGDTA

			</tbody>
		</table>
		<div id="listbottomcontrol"><a class="nondisp" id="prevlinkbot">Previous 
				$ww_listsize</a><a class="addlink" href="$pf_scriptname?task=beginmanage&mode=Add&rnd=$rnd">Add Record</a> <a class="nondisp" id="nextlinkbot">Next 
				$ww_listsize</a></div></div>
	<script type="text/javascript">

	// write the PREV link if necessary
	if ($ww_prevpage > 0) 
	{
		xl_GetObj("prevlinktop").className ="prevlink";
		xl_GetObj("prevlinkbot").className ="prevlink";
		xl_GetObj("prevlinktop").href="$pf_scriptname?page=$ww_prevpage&rnd=$rnd";
		xl_GetObj("prevlinkbot").href="$pf_scriptname?page=$ww_prevpage&rnd=$rnd";
	}
		// write the NEXT link if necessary
	if ($ww_count == $ww_listsize) 
	{
		xl_GetObj("nextlinktop").className ="nextlink";
		xl_GetObj("nextlinkbot").className ="nextlink";
		xl_GetObj("nextlinktop").href="$pf_scriptname?page=$ww_nextpage&rnd=$rnd";
		xl_GetObj("nextlinkbot").href="$pf_scriptname?page=$ww_nextpage&rnd=$rnd";
	}
	</script>
	
</body>
</html>
SEGDTA;
		return;
	}
	if($segment == "rcddisplay")
	{

		echo <<<SEGDTA
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title>Maintain customer master file - Display</title>
	
	<meta content="WebSmart" name="generator" />
	<meta http-equiv="Pragma" content="no-cache" />
	<link media="screen, tv, projection" href="/websmart/v7.2/idaho/css/screen.css" type="text/css" rel="stylesheet" />
	<link media="print" href="/websmart/v7.2/idaho/css/print.css" type="text/css" rel="stylesheet" />
	<script src="/websmart/v7.2/javascript/esdiapi010.js" type="text/javascript">
	
	
	
	</script>
</head>

<body>
	<div id="headline">
		<div id="logo" title="Logo">&nbsp;</div>
		<div id="title">Maintain customer master file</div></div>
	<div id="divider">&nbsp;</div>
	<div id="contents">
		<div id="navtop">
			
SEGDTA;

  	if ($_REQUEST['task'] == 'disp') wrtseg('RtnToList'); 
	else 
		if ($_REQUEST['task'] == 'delconf') wrtseg('DelChoice');
  			
		echo <<<SEGDTA
</div>
		<table>
			<tbody>
				<tr>
					<td>Customer Number:</td>
					<td>
						$CMCUST</td>
				</tr>
				<tr>
					<td>Customer Name:</td>
					<td>
						$CMNAME</td>
				</tr>
				<tr>
					<td>Address 1:</td>
					<td>
						$CMADR1</td>
				</tr>
				<tr>
					<td>Address 2:</td>
					<td>
						$CMADR2</td>
				</tr>
				<tr>
					<td>City:</td>
					<td>
						$CMCITY</td>
				</tr>
				<tr>
					<td>State/Prov:</td>
					<td>
						$CMSTATE</td>
				</tr>
				<tr>
					<td>Country:</td>
					<td>
						$CMCOUNT</td>
				</tr>
				<tr>
					<td>Postal/Zip Code:</td>
					<td>
						$CMPOST</td>
				</tr>
				<tr>
					<td>Area Code:</td>
					<td>
						$CMAREA</td>
				</tr>
				<tr>
					<td>Phone Number:</td>
					<td>
						$CMPHON</td>
				</tr>
				<tr>
					<td>Contact Name:</td>
					<td>
						$CMCONT</td>
				</tr>
				<tr>
					<td>Email Address:</td>
					<td>
						$CMEMAIL</td>
				</tr>
				<tr>
					<td>Terms:</td>
					<td>
						$CMTERM</td>
				</tr>
				<tr>
					<td>Customer Since:</td>
					<td>
						$CMDACR</td>
				</tr>
				<tr>
					<td>Discount Code:</td>
					<td>
						$CMDSCNT</td>
				</tr>
			</tbody>
		</table></div>
	
</body>
</html>
SEGDTA;
		return;
	}
	if($segment == "delchoice")
	{

		echo <<<SEGDTA
<p>Are you SURE you want to delete this record?</p>
<a href="$pf_scriptname?task=del&rrn=$ww_rrn">Yes</a>
&nbsp;&nbsp;
<a href="javascript:location.href='?page=$ww_page'">No</a>

SEGDTA;
		return;
	}
	if($segment == "rtntolist")
	{

		echo <<<SEGDTA

<button onClick="javascript:location.href='?page=$ww_page';">Back</button>
SEGDTA;
		return;
	}
	if($segment == "rcdmanage")
	{

		echo <<<SEGDTA
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title>Maintain customer master file - Add or Change</title>
	
	<meta content="WebSmart" name="generator" />
	<meta http-equiv="Pragma" content="no-cache" />
	<link media="screen, tv, projection" href="/websmart/v7.2/idaho/css/screen.css" type="text/css" rel="stylesheet" />
	<link media="print" href="/websmart/v7.2/idaho/css/print.css" type="text/css" rel="stylesheet" />
	<script src="/websmart/v7.2/javascript/esdiapi010.js" type="text/javascript">
	
	
	
	</script>
	<script type="text/javascript">
	
	xl_AttachEvent(window, "load", WindowInit); 

		function WindowInit()
		{
			xl_FocusFirstElement();
		}
	
	</script>
</head>

<body>
	<div id="headline">
		<div id="logo" title="Logo">&nbsp;</div>
		<div id="title">Maintain customer master file</div></div>
	<div id="divider">&nbsp;</div>
	<div id="contents">
		<form onsubmit="return xl_EnableDisabledElements(this)" action="$pf_scriptname" method="get">
			<input type="hidden" value="endmanage" name="task" /> 
			<input type="hidden" value="$ww_rrn" name="rrn" /> 
			<table>
				<tbody>
					<tr>
						<td>Customer Number:</td>
						<td>
							<input maxlength="7" size="7" value="$CMCUST" name="CMCUST" /></td>
					</tr>
					<tr>
						<td>Customer Name:</td>
						<td>
							<input maxlength="50" size="50" value="$CMNAME" name="CMNAME" /></td>
					</tr>
					<tr>
						<td>Address 1:</td>
						<td>
							<input maxlength="50" size="50" value="$CMADR1" name="CMADR1" /></td>
					</tr>
					<tr>
						<td>Address 2:</td>
						<td>
							<input maxlength="50" size="50" value="$CMADR2" name="CMADR2" /></td>
					</tr>
					<tr>
						<td>City:</td>
						<td>
							<input maxlength="20" value="$CMCITY" name="CMCITY" /></td>
					</tr>
					<tr>
						<td>State/Prov:</td>
						<td>
							<input maxlength="2" size="2" value="$CMSTATE" name="CMSTATE" /></td>
					</tr>
					<tr>
						<td>Country:</td>
						<td>
							<input maxlength="2" size="2" value="$CMCOUNT" name="CMCOUNT" /></td>
					</tr>
					<tr>
						<td>Postal/Zip Code:</td>
						<td>
							<input maxlength="7" size="7" value="$CMPOST" name="CMPOST" /></td>
					</tr>
					<tr>
						<td>Area Code:</td>
						<td>
							<input maxlength="3" size="3" value="$CMAREA" name="CMAREA" /></td>
					</tr>
					<tr>
						<td>Phone Number:</td>
						<td>
							<input maxlength="7" size="7" value="$CMPHON" name="CMPHON" /></td>
					</tr>
					<tr>
						<td>Contact Name:</td>
						<td>
							<input maxlength="50" size="50" value="$CMCONT" name="CMCONT" /></td>
					</tr>
					<tr>
						<td>Email Address:</td>
						<td>
							<input maxlength="256" size="256" value="$CMEMAIL" name="CMEMAIL" /></td>
					</tr>
					<tr>
						<td>Terms:</td>
						<td>
							<input maxlength="2" size="2" value="$CMTERM" name="CMTERM" /></td>
					</tr>
					<tr>
						<td>Customer Since:</td>
						<td>
							<input maxlength="10" size="10" value="$CMDACR" name="CMDACR" /></td>
					</tr>
					<tr>
						<td>Discount Code:</td>
						<td>
							<input maxlength="2" size="2" value="$CMDSCNT" name="CMDSCNT" /></td>
					</tr>
				</tbody>
			</table>
			<div id="navbottom">
				<input class="navbutton" type="submit" value="$ww_mode" name="mode" /> 
				<script type="text/javascript">
		// If it's a new record:
		if ($ww_rrn == 0)
		{			} 
		else 
		{				xl_DisableFormElementByName("CMCUST");
		}
	</script>
				<button class="navbutton" onclick="javascript:location.href='?page=$ww_page'">Cancel</button> </div>
		</form></div>
	
</body>
</html>
SEGDTA;
		return;
	}

	// If we reach here, the segment is not found
	echo("Segment $segment is not defined! ");
}

function internal_init()
{
	
global $CMCUST,$CMNAME,$CMADR1,$CMADR2,$CMCITY,$CMSTATE,$CMCOUNT,$CMPOST,$CMAREA,$CMPHON,$CMCONT,$CMEMAIL,$CMTERM,$CMDACR,$CMDSCNT;
	global $pf_scriptname;
	$pf_scriptname = 'custmaint.php';

	session_start();

	global $pf_task;
	if(isset($_REQUEST['task']))
		$pf_task = $_REQUEST['task'];
	else
		$pf_task = 'default';
	
	// this is an array
	global $pf_liblLibs;

$pf_liblLibs[1] = 'XL_WEBDEMO';

}
?>