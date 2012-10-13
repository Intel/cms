<?
	// #########################
	// Database Management Class (mysqli)
	// #########################

	if (!FAKE)
		exit;

	class Database {
		private static $mysqli;
        public static $queries = 0;

		public static function Initialize() {
            self::$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_DATABASE);
            
            if (self::$mysqli->connect_errno)
                die("Failed to connect to MySQL: (" . self::$mysqli->connect_errno . ") " . self::$mysqli->connect_error);
		}
		
		public static function Query($query) {
            //StopWatch::Start();
            $result = self::$mysqli->query($query);
			
            if (!$result)
                die("MySQL query failed: (" . self::$mysqli->errno . ") " . self::$mysqli->error);
            
			self::$queries++;
            //print "Query took: " . StopWatch::Stop() . "ms. Total time: " . StopWatch::GetTotal() . "\n";
            return new DBResult($result);
		}
        
        public static function Escape(&$string) {
            return self::$mysqli->escape_string($string);
        }
        
        public static function GetLastIncrId() {
            return self::$mysqli->insert_id;
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
            return $this->data->num_rows;
        }
		
		public function HasData() {
			if ($this->data->num_rows)
				return true;
			else
				return false;
		}
		
		public function GetValue($field = 0, $type = MYSQLI_ASSOC) {
            $row = $this->GetRow($type);
			return $row[$field];
		}
		
		public function GetRow($type = MYSQLI_ASSOC) {
			$row = $this->data->fetch_array($type);
            $this->SetPointer($this->pointer);
            return $row;
		}
		
		public function GetArray($type = MYSQLI_ASSOC) {
			$result = $this->data;
			$i = 0;
			while ($row = $this->data->fetch_array($type)) {
				$output[$i] = $row;
				$i++;
			}
            $this->SetPointer($this->pointer);
			return $output;
		}
        
        public function NextRow() {
            if ($this->NumRows() < $this->pointer+2)
                return false;
                
            if ($this->data->data_seek($this->pointer+1))
                $this->pointer++;
            else
                return false;
            
            return true;
        }
        
        public function SetPointer($address) {
            $this->data->data_seek($address) or die('<b>Failed seek</b><br/><b>Displaying Backtrace:</b><br/><pre>' . print_r(debug_backtrace(), true) . '</pre>');
            $this->pointer = $address;
            
            return true;
        }
	}
?>