<?
    // ########################
    // Account Management Class
    // ########################

    if (!FAKE)
        exit;

    // Settings
    define("USERNAME_MIN_LEN", 3);
    define("USERNAME_MAX_LEN", 20);
    define("PASSWORD_MIN_LEN", 6);
    define("PASSWORD_MAX_LEN", 40);

    // Errors
    define("ERROR_INVALID_CHARS", -1);
    define("ERROR_USER_EXISTS", -2);
    define("ERROR_USERNAME_LEN", -3);
    define("ERROR_PASSWORD_LEN", -4);
    define("ERROR_INVALID_EMAIL", -5);
    define("ERROR_PASSWORD_MISSMATCH", -6);
    define("ERROR_EMAIL_MISSMATCH", -7);
    
    // Usergroups
    define("USERGROUP_WAITING_FOR_EMAIL", 0);
    define("USERGROUP_REGISTERED", 1);
    define("USERGROUP_MODERATOR", 2);
    define("USERGROUP_ADMIN", 10);

    // Password generation
    define('SALT_LENGTH', 15);

    class Account {
        // Data from database
        public $m_id;
        public $m_username;
        public $m_password;
        public $m_salt;
        public $m_email;
        public $m_register_date;
        public $m_banned_date;
        public $m_usergroup;
        
        // Other Data
        public $m_loggedin;
        
        public function __construct() {
            // Load Session (if exists)
            if ($this->LoadSession())
                $this->m_loggedin = true;
            else
                $this->m_loggedin = false;
        }
        
        public function AddAccount($a_username, $a_password, $a_password_confirm, $a_email, $a_email_confirm) {
            // Field checks
            if ($a_password != $a_password_confirm)
                return ERROR_PASSWORD_MISSMATCH;
            
            if ($a_email != $a_email_confirm)
                return ERROR_EMAIL_MISSMATCH;

            if (strlen($a_username) < USERNAME_MIN_LEN || strlen($a_username) > USERNAME_MAX_LEN)
                return ERROR_USERNAME_LEN;
            
            if (strlen($a_password) < PASSWORD_MIN_LEN || strlen($a_password) > PASSWORD_MAX_LEN)
                return ERROR_PASSWORD_LEN;

            if (!$this->IsValidUsername($a_username))
                return ERROR_INVALID_CHARS;
            
            if (!$this->IsValidEmail($a_email))
                return ERROR_INVALID_EMAIL;
            
            if ($this->AccountExist($a_username))
                return ERROR_USER_EXISTS;
            
            // Escape Strings
            $email = mysql_real_escape_string($a_email);
            
            // Generate Salt and Hash Password
            $salt = '';
            $password_hash = $this->HashMe($a_password, $salt);
            
            // Insert this shitz!!!
            Database::Query("INSERT INTO `" . DB_TBL_ACCOUNT . "` (`username`, `password`, `salt`, `email`, `register_date`) VALUES
                                                                                 ('$a_username', '$password_hash', '$salt', '$email', '" . date('Y-m-d H:i:s') . "')");
            
            return true;
        }
        
        public function Login($a_username, $a_password, $a_remember) {
            // Escape String
            $username = mysql_real_escape_string($a_username);
            
            // Get Data
            $result = Database::Query("SELECT * FROM `" . DB_TBL_ACCOUNT . "` WHERE `username` = '" . $username . "'");
            
            // Is there such username?
            if (!$result->HasData())
                return false;
            
            // Generate Hash
            $salt = $result->GetValue('salt');
            $password_hash = $this->HashMe($a_password, $salt);
            
            // Check if password is correct
            if ($result->GetValue('password') != $password_hash)
                return false;
            
            // Set User Data
            $user = $result->GetRow();
            foreach($user as $key => $value) {
                // Set Class Data
                $this->{"m_" . $key} = $value;
            }
            
            // Start session (cookies)
            if (!$this->StartSession($a_remember))
                die('Could not start session!');
            
            // Change user state
            $this->m_loggedin = true;
            
            return true;
        }
        
        public function Logout() {
            // Check is user is logged in
            if (!$this->m_loggedin)
                return false;
            
            // Delete cookies
            UDelCookie('userid');
            UDelCookie('session');
            
            // Clear all user data and change state
            $this->ClearData();
            $this->m_loggedin = false;
            
            return true;
        }
        
        // ################################################################################
        // SESSIONS
        // ################################################################################
        //
        // Load session (if exists)
        public function LoadSession() {
            // Check Cookies
            if (!UGetCookie('userid'))
                return false;
            
            if (!UGetCookie('session'))
                return false;
            
            // Escape string
            $userid = mysql_real_escape_string(UGetCookie('userid'));
            // Query for user
            
            $account_data = Database::Query("SELECT * FROM `" . DB_TBL_ACCOUNT . "` WHERE `id` = '" . $userid . "';");
            
            if (!$account_data->HasData())
                return false;
            
            // Query session data
            $session_data = Database::Query("SELECT * FROM `" . DB_TBL_ACCOUNT_SESSIONS . "` WHERE `id` = '" . $userid . "';");
            
            if (!$session_data->HasData())
                return false;
            
            if (UGetCookie('session') != $session_data->GetValue('hash'))
                return false;
            
            // Set User Data
            $user = $account_data->GetRow();
            foreach($user as $key => $value) {
                $this->{"m_" . $key} = $value;
            }
            
            return true;
        }
        //
        // Start new session (add cookies)
        public function StartSession($a_remember) {
            if (!isset($this->m_id) || !isset($this->m_password))
                return false;
            
            // Get client IP
            $ip = $_SERVER['REMOTE_ADDR'];
            
            // Generate session hash
            $session_hash = hash('sha512', $this->m_password . $ip);
            
            // Add session
            Database::Query("REPLACE INTO `" . DB_TBL_ACCOUNT_SESSIONS . "` (`id`, `hash`, `date`) VALUES ('" . $this->m_id . "', '" . $session_hash . "', FROM_UNIXTIME('" . Date("U") . "'));");
            
            if ($a_remember) {
                // Long expire time (600days)
                $expire_time = time() + 60*60*24*600;
                
                // Set Cookies
                USetCookie('userid', $this->m_id, $expire_time);
                USetCookie('session', $session_hash, $expire_time);
            } else {
                // Cookie expires on session end
                USetCookie('userid', $this->m_id);
                USetCookie('session', $session_hash);
            }
            
            return true;
        }
        //
        // ################################################################################
        
        
        

        // ################################################################################
        // PASSWORD HASHING
        // ################################################################################
        //
        // Hash Password (and get salt)
        private function HashMe($a_password, &$a_salt) {
            if ($a_salt == '') {
                // Generate Salt
                $salt = substr(hash('sha512',uniqid(rand(), true).$key.microtime()), 0, SALT_LENGTH);
            } else {
                // Use Existing One
                $salt = substr($a_salt, 0, SALT_LENGTH);
            }
            
            // Hash
            return hash('sha512', ($salt . $a_password));
        }
        //
        // ################################################################################
        
        
        
        
        // ################################################################################
        // USER FIELD FUNCTIONS
        // ################################################################################
        //
        // Validate Username
        private static function IsValidUsername($a_username) {
            if (preg_replace('/[a-zA-Z0-9]/', '', $a_username))
                return false;
            else
                return true;
        }
        //
        // Validate Email
        private static function IsValidEmail($a_email) {
            if (preg_match("/^(\w+((-\w+)|(\w.\w+))*)\@(\w+((\.|-)\w+)*\.\w+$)/", $a_email))
                return true;
            else
                return false;
        }
        //
        // Check for existing username
        private function AccountExist($a_username) {
            $result = Database::Query("SELECT * FROM `" . DB_TBL_ACCOUNT . "` WHERE `username` = '" . $a_username . "'");
            if ($result->HasData())
                return true;
            else
                return false;
        }
        //
        // Clear all user data
        public function ClearData() {
            unset($this->m_id);
            unset($this->m_username);
            unset($this->m_password);
            unset($this->m_salt);
            unset($this->m_email);
            unset($this->m_register_date);
            unset($this->m_banned_date);
            unset($this->m_usergroup);
        }
        //
        // ################################################################################
    }
?>