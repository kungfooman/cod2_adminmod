<?php
	class cMySQL
	{
		public $link;
		function __construct($info)
		{
			$this->link = mysql_connect(
				$info["server"],
				$info["user"],
				$info["pass"]
			);

			if (!$this->link)
				throw new Exception($this->lastError());

			$ret = mysql_select_db($info["database"], $this->link);

			if (!$ret)
				throw new Exception($this->lastError());
		}
		function query($query)
		{
			return new cMySQLQuery($query, $this);
		}
		function lastError()
		{
			if ($this->link)
				return mysql_error($this->link);
			else
				return mysql_error();
		}
		function close()
		{
			mysql_close($this->link);
		}
		function insertId()
		{
			return mysql_insert_id($this->link);
			//return $this->query("SELECT LAST_INSERT_ID()");
		}
		function escape($string)
		{
			return mysql_real_escape_string($string, $this->link);
		}
	}

	class cMySQLQuery
	{
		public $query;
		function __construct($strQuery, $linkInstance)
		{
			$this->query = mysql_query($strQuery, $linkInstance->link);
			if (!$this->query)
				throw new Exception($linkInstance->lastError());
		}
		function numRows()
		{
			return mysql_num_rows($this->query);
		}
		function fetchArray()
		{
			return mysql_fetch_array($this->query);
		}
		function free()
		{
			mysql_free_result($this->query);
		}
	}
?>