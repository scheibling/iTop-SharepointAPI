<?php
require_once "/var/www/html/itop/extensions/SharepointAPI/SharePointAPI.php"; //Change depending on location
$folderid = preg_replace('/\D/', '', $_GET['id']);
use SPAPI\API;
$sp = new API(); 
$result = $sp->read($folderid); 
echo "<p><a style='color:darkblue;font-size:1.8em;' href='https://SITEURL.sharepoint.com/sites/ItopTest/".$folderid."/Forms/AllItems.aspx'><u>Länk till dokumentmapp</u></a></p>"; //Insert site URL here
echo "";
echo "<h1><span style='color:darkblue;'>Mappar & Filer</span>:</h1>";
if ($result['warning'])
{
	die("No files currently in the folder");
}

foreach ($result as $file){
	$fileref = trim(substr($file['fileref'], strpos($file['fileref'], '#') + 1));
	$insert = "/";
	if (strpos($fileref, '.'))$insert = "";
	echo "<p><a style='color:darkblue;font-size:1.3em;'href='https://SITEURL.sharepoint.com/".$fileref."'>".$file['linkfilename'].$insert."</a></p>";
}
?>