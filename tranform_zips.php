<?php
$lines = file("zips.txt");
foreach ($lines as $line) {
  $thisary = explode(",", $line);
  echo '"' . trim($thisary[0]) . '",';
}


?>
