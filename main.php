<?php
	require_once("classes/db.php");
	require_once("classes/config.php");
	require_once("classes/rcon.php");
	require_once("functions/logfile.php");
	require_once("functions/time.php");

	$rcon = new cRcon(cConfig::rcon());
	$useMySQL = 1;
	try {
		$db = new cMySQL(cConfig::database());
	} catch (Exception $e) {
		echo "[WARNING] " . $e->getMessage() . "\n";
		$useMySQL = 0;
	}

	function canGuidExecuteCommand($guid, $command)
	{
		global $db;

		$guid = $db->escape($guid);
		$command = $db->escape($command);

		$query = $db->query("
			SELECT
				1
			FROM
				players
			INNER JOIN
				playerisingroups
			ON
				players.id = playerisingroups.playerId
			AND
				players.guid = '$guid'
			INNER JOIN
				commandisingroups
			ON
				playerisingroups.groupId = commandisingroups.groupId
			INNER JOIN
				commands
			ON
				commands.id = commandisingroups.commandId
			AND
				commands.command = '$command'
		");

		$rows = $query->numRows();
		$query->free();

		if ($rows != 0)
			return 1;
		else
			return 0;
	}

	function parseLine($line)
	{
		// search the end of time... result in i
		for ($i=3; $i<20; $i++)
			if ($line{$i} == " ")
				break;
		$substrTo = $i;

		//$tillSpace = explode(" ", $line);
		//$substrTo = strlen($tillSpace[0]);

		$strTime = trim(substr($line, 0, $substrTo));
		$time = timeToSeconds($strTime);

		echo "substrto=$substrTo";

		$line = trim(substr($line, $substrTo)); // 7

		$cmd = explode(";", $line);

		echo "cmd0=".$cmd[0]."\n";

		if ($cmd[0] == "say" || $cmd[0] == "sayteam")
		{
			$log = array(
				"cmd" => $cmd[0],
				"guid" => $cmd[1],
				"id" => $cmd[2],
				"name" => $cmd[3],
				"message" => $cmd[4]
			);

			$firstCharacter = ord($log["message"]{0});
			if ($firstCharacter == 20)
				$type = "quickchat";
			else if ($firstCharacter == 21)
				$type = "chat";
			else
				$type = "console";

			if ($type != "console")
				$text = trim(substr($log["message"], 1));
			else
				$text = trim($log["message"]);

			// just spaces got written in chat
			if (strlen($text) == 0)
				return 0;

			// split the message to arguments
			$args = explode(" ", $text);
			$realargs = array();
			foreach ($args as $arg)
				if (strlen($arg) > 0)
					$realargs[] = $arg;
			$arguments = $realargs;

			// specify the commandtype and delete the marker if its a command
			$commandtype = "none";
			if (strlen($arguments[0]) > 1) // strlen("!")==1 is no command
			{
				if ($arguments[0]{0} == "!") {
					$commandtype = "private";
					$arguments[0] = substr($arguments[0], 1);
				} else if ($arguments[0]{0} == "@") {
					$commandtype = "public";
					$arguments[0] = substr($arguments[0], 1);
				}
			}

			$ret = array();
			$ret["time"] = $time;
			$ret["cmd"] = $log["cmd"];
			$ret["guid"] = $log["guid"];
			$ret["id"] = $log["id"];
			$ret["name"] = $log["name"];
			$ret["type"] = $type;
			$ret["message"] = $text;
			$ret["commandtype"] = $commandtype;
			$ret["arguments"] = $arguments;
			return $ret;
		} else if ($cmd[0] == "J") {
			$ret = array();
			$ret["time"] = $time;
			$ret["cmd"] = "join";
			$ret["guid"] = $cmd[1];
			$ret["id"] = $cmd[2];
			$ret["name"] = $cmd[3];
			return $ret;
		} else {
			// K, D, ... dont care about it
			return 0;
		}
	}
		function getPlayerIdByGuid($guid)
		{
			global $db;

			$query = $db->query("
				SELECT
					id
				FROM
					players
				WHERE
					guid = '$guid'
			");
			$numRows = $query->numRows();
			$row = $query->fetchArray();
			$id = $row["id"];
			$query->free();
			if ($numRows == 0)
				return false;
			else
				return $id;
		}
		function getGroupIdByName($name)
		{
			global $db;

			$query = $db->query("
				SELECT
					id
				FROM
					groups
				WHERE
					name = '$name'
			");
			$numRows = $query->numRows();
			$row = $query->fetchArray();
			$id = $row["id"];
			$query->free();
			if ($numRows == 0)
				return false;
			else
				return $id;
		}

		function putPlayerIdInGroupId($playerId, $groupId)
		{
			global $lastErrorMessage;
			global $db;



			// connectPlayerIdWithGroupId($playerId, $groupId)
			// todo: add check if the connection is already made
			$query = $db->query("
				INSERT INTO
					playerisingroups (playerId, groupId)
				VALUES
					($playerId, $groupId)
			");

		}

		function modSay($log, $message)
		{
			global $rcon;

			if ($log["commandtype"] == "private")
				$rcon->execute("tell {$log["id"]} ^8[PM] ^7" . $message);
			if ($log["commandtype"] == "public")
				$rcon->execute("say ^8[BOT] ^7" . $message);
		}

		function registerNewPlayer($guid)
		{
			global $db;

			$query = $db->query("
				INSERT INTO
					players (guid)
				VALUES
					('$guid')
			");
			$playerId = $db->insertId();
			echo "DBINSERTID: $playerId\n";
			return $playerId;
		}

		function registerGuid($guid)
		{
			global $lastErrorMessage;

			/*
				Sicherzustellen:
				1. der User ist noch nicht registriert
				2. die Gruppe gibt es
			*/

			$playerId = getPlayerIdByGuid($guid);
			if ($playerId !== false)
			{
				$lastErrorMessage = "you are already registered (@$playerId).";
				return false;
			}

			$groupId = getGroupIdByName("users");
			if ($groupId === false)
			{
				$lastErrorMessage = "group with name \"users\" does not exist";
				return false;
			}

			$playerId = registerNewPlayer($guid);
			echo "playerId: $playerId\n";
			// nice error-message?
			// if ($playerId === false)
			// 	$lastErrorMessage = "couldnt register a new user ($lastErrorMessage)";


			putPlayerIdInGroupId($playerId, $groupId);
			return $playerId;
		}

		function addGroup($name)
		{
			global $db, $lastErrorMessage;

			$name = $db->escape($name);
			$query = $db->query("
				INSERT IGNORE INTO
					groups (name)
				VALUES
					('$name')
			");
		}
	/*
		cMod
		{
			onNameChange()
			onTextMessage()
		}
	*/


	class cExecFile
	{
		public $filename;
		public $commands;

		function __construct($filename)
		{
			$this->commands = "";
			$this->filename = $filename;
		}
		function addCommand($command)
		{
			$this->commands .= $command . "\n";
		}
		// make the parsed log available to the gameserver
		function addLog($log)
		{
			$output = "";
			$output .= "set time \"{$log["time"]}\"\n";
			$output .= "set cmd \"{$log["cmd"]}\"\n";
			$output .= "set guid \"{$log["guid"]}\"\n";
			$output .= "set id \"{$log["id"]}\"\n";
			$output .= "set name \"{$log["name"]}\"\n";
			$output .= "set type \"{$log["type"]}\"\n";
			$output .= "set message \"{$log["message"]}\"\n";
			$output .= "set commandtype \"{$log["commandtype"]}\"\n";
			// max 10 args
			for ($i=0; $i<10; $i++)
			{
				$value = ""; // overwrite the arg of the last toExec-file
				if (isset($log["arguments"][$i]))
					$value = $log["arguments"][$i];
				$output .= "set arg$i \"$value\"\n";
			}
			$this->addCommand($output);
			// tell the while(1) in gsc that there is new data
			$this->addCommand("set run \"1\"");
		}
		function say($log, $message)
		{
			if ($log["commandtype"] == "private")
				$this->addCommand("tell {$log["id"]} ^8[PM] ^7" . $message);
			if ($log["commandtype"] == "public")
				$this->addCommand("say ^8[BOT] ^7" . $message);
		}
		function execute()
		{
			global $rcon;
			$tmp = fopen($this->filename, "w");
			fwrite($tmp, $this->commands);
			fclose($tmp);
			$rcon->execute("exec toExec.cfg");
			// flush the content
			$this->commands = "";
		}
		/*
		function executeInfo($info)
		{
			pb_sv_kicklen 0
		}
		*/
	}


	$files = cConfig::files();
	$fileLog = $files["log"];
	$fileToExec = $files["toExec"];


	$execFile = new cExecFile($fileToExec);
	$file = @fopen($fileLog, "r+");

	if (!$file)
		die("ERROR: fopen(\"$location\", \"r\") failed!");
	else
		echo "[OPEN] $fileLog\n";

	fseek($file, 0, SEEK_END);

	echo "entering main-loop... write to $fileToExec\n";
	while (1)
	{
		try {
		$rawMsg = fread($file, 1024);
		if (strlen($rawMsg) == 0)
		{
			usleep(250);
			continue;
		}

		$log = parseLine($rawMsg);
		if (!$log)
			continue;

		if ($log["cmd"] == "join")
		{
			echo "[JOIN] guid={$log["guid"]} id={$log["id"]} name=\"{$log["name"]}\"\n";
			$execFile->addCommand("say you joined xD");
			continue;
		}

		if ($log["commandtype"] == "none")
			continue;

		// shorter...
		$args = $log["arguments"];
		$arg0 = isset($args[0]) ? $args[0] : "";
		$arg1 = isset($args[1]) ? $args[1] : "";
		$arg2 = isset($args[2]) ? $args[2] : "";
		$arg3 = isset($args[3]) ? $args[3] : "";
		$arg4 = isset($args[4]) ? $args[4] : "";
		$arg5 = isset($args[5]) ? $args[5] : "";
		$arg6 = isset($args[6]) ? $args[6] : "";
		$arg7 = isset($args[7]) ? $args[7] : "";
		$arg8 = isset($args[8]) ? $args[8] : "";
		$arg9 = isset($args[9]) ? $args[9] : "";
		// nicer...
		$cmd = $arg0;

		echo "arg0 $arg0\n";
		echo "arg1 $arg1\n";
		echo "arg2 $arg2\n";
		echo "arg3 $arg3\n";
		echo "arg4 $arg4\n";

		$lastErrorMessage = "";

		// commands with preworked arguments...
		if ($cmd == "time")
			$execFile->addCommand("set arg1 \"".timeToSeconds($args[1])."\"");
		if ($cmd == "saybold")
		{
			$partWithoutCmd = "";
			for ($i=1; $i<count($args); $i++)
				$partWithoutCmd .= $args[$i] . "  ";
			$partWithoutCmd = trim($partWithoutCmd);
			$execFile->addCommand("set partWithoutCmd \"$partWithoutCmd\"");
		}

		if ($cmd == "register")
		{
			// putGuidInGroup()? would be more general... also for !putgroup
			// you are already in the group "users" (playerId: @2)
			// vs. die schöne? fehlermeldung:
			// you are already registered (@$playerId)
			$newPlayerId = registerGuid($log["guid"]);
			if ($newPlayerId !== false)
			{
				$execFile->say($log, "you are now registered (@$newPlayerId).");
			} else {
				// already registered...
				// group does not exists...
				$execFile->say($log, $lastErrorMessage);
			}

			// $ret = putGuidInGroup($log["guid"], "users");

			$execFile->execute();
			continue;
		}

		if ($cmd == "me")
		{
			$playerId = getPlayerIdByGuid($log["guid"]);

			if ($playerId === false)
			{
				$execFile->say($log, ">>>not registered<<<");
				//goto finishWork;
				$execFile->execute();
				continue;
			}

			// getGroupNamesByPlayerId($playerId)
			$query = $db->query("
				SELECT
					groups.name
				FROM
					playerisingroups
				INNER JOIN
					groups
				ON
					playerisingroups.groupId = groups.id
				AND
					playerisingroups.playerId = $playerId
			");

			$groups = "";
			while ($row = $query->fetchArray())
				$groups .= $row["name"] . " ";
			$groups = trim($groups);

			$query->free();

			// todo: registered since...
			$execFile->say($log, "id=$playerId groups=$groups");

			$execFile->execute();
			continue;
		}

		$isAdmin = false;
		$adminGuids = cConfig::admins();
		foreach ($adminGuids as $adminGuid)
			if ($log["guid"] == $adminGuid)
				$isAdmin = true;

		if (!$isAdmin) // || 1)
		{
			if (!canGuidExecuteCommand($log["guid"], $cmd))
			{
				$execFile->say($log, "Sorry Sweety, something went wrong! :(");
				//goto finishWork;
				$execFile->execute();
				continue;
			}

			if ($cmd == "help")
			{
				$execFile->say("Commands: !firewalk $time - !fireeyes $time - !firestop");
				//goto finishWork;
				$execFile->execute();
				continue;
			}

			// handle scripted user commands...
			$execFile->addLog($log);
			//goto finishWork;
			$execFile->execute();
			continue;
		}

		// handle admin commands...

		// todo: unknownCommand-check for admins

		//!addgroup 
		if ($cmd == "addgroup")
		{
			$groupname = $arg1;
			addGroup($groupname); // todo: add $ret
			$execFile->say($log, "Group \"$groupname\" got added!");
		}

		// working without mysql
		if ($cmd == "mod")
		{
			// !mod port 28961
			if ($arg1 == "port")
			{
				$rcon->changePort($arg2);
				$execFile->say($log, "Port got changed!");
			}
		}

		// !listgroup admins
		if ($cmd == "listcommands")
		{
			$group = $arg1;
			$query = $db->query("
				SELECT
					command
				FROM
					commands
				INNER JOIN
					commandisingroups
				ON
					commandisingroups.commandId = commands.id
				INNER JOIN
					groups
				ON
					groups.id = commandisingroups.groupId
				AND
					groups.name = '$group'
			");
			$commands = "";
			while ($row = $query->fetchArray())
				$commands .= $row["command"] . " ";
			$commands = trim($commands);
			$query->free();
			$rcon->execute("tell {$log["id"]} ^8[PM] ^7commands: $commands^8...");
		}

		// !addcommand admins map
		// todo: check if the connection is already made...
		if ($cmd == "addcommand")
		{
			$group = $arg1;
			$command = $arg2; // mysql_real...

			$query = $db->query("
				SELECT
					id
				FROM
					commands
				WHERE
					command = '$command'
			");
			if ($query->numRows() != 0)
			{
				$row = $query->fetchArray();
				$commandId = $row["id"];
				$query->free();
			} else {
				// getCommandIdInsertOrSelect()...
				$db->query("
					INSERT INTO
						commands (command)
					VALUES
						('$command')
				");
				$commandId = mysql_insert_id();
			}
			$query = $db->query("
				SELECT
					id
				FROM
					groups
				WHERE
					name = '$group'
			");
			$row = $query->fetchArray();
			$groupId = $row["id"];
			$query->free();
			
			$db->query("
				INSERT
					commandisingroups (commandId, groupId)
				VALUES
					($commandId, $groupId)
			");
		}

		if ($cmd == "tempban")
		{
			// ban in ban.txt
			// cronjob which deletes the entry after the amount of time
			// such a good job for an application server
		}
		/*if ($cmd == "ban")
		{
			// !ban aName
			// !ban id
			// !ban "123123231" -> NOT A NUMBER
			$execFile->addCommand("banClient $arg1");
		}
		if ($cmd == "kick")
		{
			$execFile->addCommand("clientKick $arg1");
		}*/
		if ($cmd == "unban")
		{
			// parse ban.txt
			// delete the guid
			// rewrite the file

			// handles of player to get the guid:
			//!unban @123
			//!unban ""
		}
		if ($cmd == "zipmod")
		{
			$oldDir = getcwd();
			chdir("/home/kungy12345/zombie/zombie0.1/");
			exec("./zipmod.sh", $output, $ret);
			chdir($oldDir);
			//echo $output;
			$execFile->say($log, "zipmod=$ret");
		}
		/*
			!iptables ban 123.123.*
			!rcon status
		*/

	/*
		$output = "";
		$output .= "time=" . $log["time"] . " ";
		$output .= "cmd=" . $log["cmd"] . " ";
		$output .= "guid=" . $log["guid"] . " ";
		$output .= "id=" . $log["id"] . " ";
		$output .= "name=\"" . $log["name"] . "\" ";
		$output .= "type=" . $log["type"] . " ";
		$output .= "message=\"" . $log["message"] . "\" ";
		$output .= "commandtype=" . $log["commandtype"] . " ";
		$output .= "arguments=";
		foreach ($log["arguments"] as $i=>$argument)
			$output .= "[$i]$argument";
		$output = trim($output);
		$output .= "\n";
		echo $output;
	*/

		
		$execFile->addLog($log);
	/*
		// commands with preworked arguments...
		if ($cmd == "time")
			$execFile->addCommand("set arg1 \"".timeToSeconds($args[1])."\"");
		if ($cmd == "saybold")
		{
			$partWithoutCmd = "";
			for ($i=1; $i<count($args); $i++)
				$partWithoutCmd .= $args[$i] . "  ";
			$partWithoutCmd = trim($partWithoutCmd);
			$execFile->addCommand("set partWithoutCmd \"$partWithoutCmd\"");
		}
	*/
		$execFile->execute();

		// goto finishWork;
		//finishWork:
		//{
		//	$execFile->execute();
		//}
		} catch (Exception $e) {
			echo "Exception!\n";
		}
	} // while (1)
?>