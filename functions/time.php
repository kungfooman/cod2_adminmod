<?php
	function timeToSeconds($str)
	{
		$parts = explode(":", $str);

		$seconds = 0;
		$minutes = 0;
		$hours = 0;

		switch (count($parts))
		{
			case 1:
				$seconds = $parts[0];
				break;
			case 2:
				$seconds = $parts[1];
				$minutes = $parts[0];
				break;
			case 3:
				$seconds = $parts[2];
				$minutes = $parts[1];
				$hours = $parts[0];
				break;
		}
		return ($hours*60*60) + ($minutes*60) + ($seconds);
	}
?>