<?php
	// #########################
	// Database Management Class
	// #########################

	if (!FAKE)
		exit;

	class Database {
		private static $mysql_conn;
        public static $queries = 0;

		public static function Initialize() {
			self::$mysql_conn = mysql_connect(DB_HOST, DB_USER, DB_PASS) or die('Could not connect to mysql server:' . mysql_error());
            mysql_select_db(DB_DATABASE, self::$mysql_conn) or die('Could not select database: ' . mysql_error());
		}
		
		public static function Query($query) {
            //StopWatch::Start();
			$result = mysql_query($query, self::$mysql_conn) or die('<b>Invalid query</b><br/>Query: ' . $query . '<br/>Mysql Error: ' . mysql_error(self::$mysql_conn) . '<br/><b>Displaying Backtrace:</b><br/><pre>' . print_r(debug_backtrace(), true) . '</pre>');
			self::$queries++;
            //print "Query took: " . StopWatch::Stop() . "ms. Total time: " . StopWatch::GetTotal() . "\n";
            return new DBResult($result);
		}
        
        public static function Escape(&$string) {
            return mysql_real_escape_string($string, self::$mysql_conn);
        }
        
        public static function GetLastIncrId() {
            return mysql_insert_id(self::$mysql_conn);
        }
	}
	
	class DBResult {
		public $data;
        public $pointer;
		
		public function __construct($obj) {
			$this->data = $obj;
            $this->pointer = 0;
		}
        
        public function NumRows() {
            return mysql_num_rows($this->data);
        }
		
		public function HasData() {
			if (mysql_num_rows($this->data))
				return true;
			else
				return false;
		}
		
		public function GetValue($field = 0, $type = MYSQL_ASSOC) {
            $row = $this->GetRow($type);
			return $row[$field];
		}
		
		public function GetRow($type = MYSQL_ASSOC) {
			$row = mysql_fetch_array($this->data, $type);
            $this->SetPointer($this->pointer);
            return $row;
		}
		
		public function GetArray($type = MYSQL_ASSOC) {
			$result = $this->data;
			$i = 0;
			while ($row = mysql_fetch_array($result, $type)) {
				$output[$i] = $row;
				$i++;
			}
            $this->SetPointer($this->pointer);
			return $output;
		}
        
        public function NextRow() {
            if ($this->NumRows() < $this->pointer+2)
                return false;
                
            if (mysql_data_seek($this->data, $this->pointer+1))
                $this->pointer++;
            else
                return false;
            
            return true;
        }
        
        public function SetPointer($address) {
            mysql_data_seek($this->data, $address) or die('<b>Failed seek</b><br/><b>Displaying Backtrace:</b><br/><pre>' . print_r(debug_backtrace(), true) . '</pre>');
            $this->pointer = $address;
            
            return true;
        }
	}
?>