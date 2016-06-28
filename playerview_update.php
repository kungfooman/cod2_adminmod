<?php
	require_once("classes/db.php");
	require_once("classes/config.php");
	require_once("classes/rcon.php");

	$rcon = new cRcon(cConfig::rcon());

	while (1)
	{
		$msg = $rcon->execute("status");
		echo $msg;
		usleep(100*1000); // 1.000.000micro = 1.000ms = 1s
	}
?>