<?php
	function parseClientMsg($cmd)
	{
		$firstChar = ord($cmd[4][0]);
		//echo "firstChar:$firstChar-";
		if ($firstChar == 21) // NAK = negative acknowledgement
			$text = trim(substr($cmd[4], 1)); // player chat with 0x21
		else
			$text = trim($cmd[4]); // from console without 0x21
		//echo "you sayd: $text";

		$args = explode(" ", $text);
		$realargs = array();
		foreach ($args as $arg)
			if (strlen(trim($arg))>0)
				$realargs[] = trim($arg);
		return $realargs;
	}

	function handleClientMsg($client, $args)
	{
		global $rcon;

		echo "MSG by guid [".$client["guid"]."]: " . $args[0] . "\n";
		if ($args[0] == "!map")
		{
			$rcon->execute("map " . $args[1]);
		}

		if ($args[0][0] == "!")
		{
			$partWithoutCmd = "";
			foreach ($args as $key=>$arg)
				if ($key != 0)
					$partWithoutCmd .= $arg." ";
			$partWithoutCmd = trim($partWithoutCmd);

			$msg = "";
			if ($args[0] == "!time")
				$args[1] = timeToSeconds($args[1]);
			if ($args[0] == "!ammo")
			{
				$msg .= "set phpName \"{$args[1]}\";";
				$msg .= "set phpSlot \"{$args[2]}\";";
				$msg .= "set phpAmmo \"{$args[3]}\";";
			}
			$msg .= "set arg0 \"{$args[0]}\";";
			$msg .= "set arg1 \"{$client["guid"]}\";";
			$msg .= "set arg2 \"{$args[1]}\";";
			$msg .= "set arg3 \"{$args[2]}\";";
			$msg .= "set arg4 \"{$args[3]}\";";
			$msg .= "set arg5 \"{$args[4]}\";";
			$msg .= "set arg6 \"{$args[5]}\";";
			$msg .= "set partWithoutCmd \"{$partWithoutCmd}\";";
			$msg .= "set run \"1\"";
			$tmp = fopen("Z:/COD2MODDING/SERVER/rickroll/toExec.cfg", "w");
			fwrite($tmp, $msg);
			fclose($tmp);
			$rcon->execute("exec toExec");
		}
	}
?>