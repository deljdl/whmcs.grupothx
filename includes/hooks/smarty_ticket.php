<?php
$checkvars = array('subject','message');
foreach ($checkvars AS $checkvar) if (strpos($_REQUEST[$checkvar],'{php}')!==false && $_SESSION['adminid'] != '1') die("THX Group - no thanks");
?>