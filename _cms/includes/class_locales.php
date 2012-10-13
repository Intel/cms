<?
	// #######################
	// Locale Management Class
	// #######################

	if (!FAKE)
		exit;
	
	class Locales {
        // Data
        public static $m_locales = array();
        public static $m_const_strings = array();
        public static $m_db_strings = array();
        
        // Options
        public static $m_locale;
		
		public static function Initialize() {
			self::LoadLocales();
            
            self::SetUserLocale();
		}
        
        public static function LoadLocales() {
            $loc_files = find_all_files(LOCALES_DIR);
            foreach ($loc_files as $loc) {
                // Remove ".ini"
                self::$m_locales[] = substr($loc, 0, -4);
            }
        }
        
        public static function InitLocale($a_locale) {
            self::$m_const_strings[$a_locale] = parse_ini_file(LOCALES_DIR . '/' . $a_locale . '.ini');
            self::LoadDBStrings($a_locale);
        }
        
        public static function LoadDBStrings($a_locale) {
            $result = Database::Query("SELECT * FROM `" . DB_TBL_STRINGS . "` WHERE `locale` IN ('', '" . $a_locale . "')");
            
            if ($result->HasData()) {
                do {
                    $row = $result->GetRow();
                    $id = $row['id'];
                    self::$m_db_strings[$a_locale][$id] = $row;
                } while ($result->NextRow());
            }
        }
        
        public static function SetUserLocale($a_locale = NULL) {
            if ($a_locale) {
                if (!in_array($a_locale, self::$m_locales))
                    die("Locale not found");
                
                self::$m_locale = $a_locale;
                USetCookie('locale', $a_locale, time() + 60*60*24*600);
            } else if ($locale = UGetCookie('locale')) {
                self::$m_locale = $locale;
            } else {
                self::$m_locale = LOCALES_DEFAULT;
            }
        }
        
        public static function GetConstString($a_key, $a_locale = NULL, $a_vars = NULL)
        {
            if (!$a_locale)
                $a_locale = self::$m_locale;
            
            if (!isset(self::$m_const_strings[$a_locale]))
                self::InitLocale($a_locale);
            
            if (!isset(self::$m_const_strings[$a_locale][$a_key]))
                return "UNDEFINED_CONST_STRING";
            
            if ($a_vars) {
                $args = array_slice(func_get_args(), 2);
                return BuildString(self::$m_const_strings[$a_locale][$a_key], $args);
            } else
                return self::$m_const_strings[$a_locale][$a_key];
        }
        
        public static function GetDBData($a_key, $a_locale = NULL)
        {
            if (!$a_locale)
                $a_locale = self::$m_locale;
            
            if (!isset(self::$m_db_strings[$a_locale]))
                self::InitLocale($a_locale);
            
            if (!isset(self::$m_db_strings[$a_locale][$a_key]))
                return;
            
            return self::$m_db_strings[$a_locale][$a_key];
        }
        
        public static function WriteStringData($a_id, $a_moduleid, $a_locale, $a_data) {
            $id = Database::Escape($a_id);
            $moduleid = Database::Escape($a_moduleid);
            $locale = Database::Escape($a_locale);
            $data = Database::Escape(serialize($a_data));
            
            Database::Query("INSERT INTO `" . DB_TBL_STRINGS . "` (`id`, `moduleid`, `locale`, `string`) VALUES ('" . $id . "', '" . $moduleid . "', '" . $locale . "', '" . $data . "')");
        }
        
        public static function ReadStringData($a_id, $a_locale = NULL) {
            $data = self::GetDBData($a_id, $a_locale);
            return unserialize($data['string']);
        }
        
        public static function getStringOrJSONLocale($string) {
            $json = json_decode($string, true);
            if (is_array($json)) {
                if (isset($json[self::$m_locale]))
                    return $json[self::$m_locale];
                else
                    return "UNDEFINED_JSON_LOC";
            } else
                return $string;
        }
	}
?>