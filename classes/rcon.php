<?php
	class cRcon
	{
		public $server;
		public $port;
		public $pass;

		function __construct($info)
		{
			$this->server = $info["server"];
			$this->port = $info["port"];
			$this->pass = $info["pass"];
		}

		function execute($cmd)
		{
			$server = $this->server;
			$port = $this->port;
			$pass = $this->pass;

			$file = fsockopen("udp://$server", $port, $errno, $errstr, 4);

			if (!$file)
				return false;

			$prefix = "\xFF\xFF\xFF\xFF";
			$query = $prefix . "rcon \"$pass\" " . $cmd;
			fwrite($file, $query);

			//echo "$server $port $pass";

			fclose($file);
		}

		function changePort($port)
		{
			$this->port = $port;
		}
	}
?>