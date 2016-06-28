<?php
	class cConfig
	{
		static $version = "home";
		//static $version = "public";

		static function database()
		{
			$info = array();

			if (self::$version == "home")
			{
				//$info["server"] = "127.0.0.1";
				//$info["user"] = "123123231";
				//$info["pass"] = "thisismypass";
				//$info["database"] = "callofduty2";
				$info["server"] = "127.0.0.1";
				$info["user"] = "root";
				$info["pass"] = "";
				$info["database"] = "callofduty2";
			}

			if (self::$version == "public")
			{
				$info["server"] = "127.0.0.1";
				$info["user"] = "123123231";
				$info["pass"] = "thisismypass";
				$info["database"] = "callofduty2";
			}

			return $info;
		}
		static function rcon()
		{
			$info = array();

			if (self::$version == "home")
			{
				$info["server"] = "127.0.0.1";
				$info["port"] = "28961";
				$info["pass"] = "asd";
			}

			if (self::$version == "public")
			{
				$info["server"] = "127.0.0.1";
				$info["port"] = "10000";
				$info["pass"] = "holla12345";
			}

			return $info;
		}
		static function files()
		{
			$info = array();

			if (self::$version == "home")
			{
				$info["log"] = "Z:/COD2MODDING/SERVER/zombie0.2/games_mp.log";
				$info["toExec"] = "Z:/COD2MODDING/SERVER/zombie0.2/toExec.cfg";
			}

			if (self::$version == "public")
			{
				$info["log"] = "../zombie/.callofduty2/zombie0.1/games_mp.log";
				$info["toExec"] = "../zombie/zombie0.1/toExec.cfg";
			}

			return $info;
		}

		static function admins()
		{
			$admins = array(
				// 1.3
				"0", // local server
				"1275733", // ich #1
				"629770", // ich #2
				"1571009", // Brutzel

				// 1.0
				"181084", // jumper
				"1456968" // sense

				// failed
				//"1432102", // we1n #1
				//"1656654", // we1n #2
				//"1399431", // armor
			);
			return $admins;
		}
	}
?>