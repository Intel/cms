<?
    if (get_magic_quotes_gpc()) {
        $process = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);
        while (list($key, $val) = each($process)) {
            foreach ($val as $k => $v) {
                unset($process[$key][$k]);
                if (is_array($v)) {
                    $process[$key][stripslashes($k)] = $v;
                    $process[] = &$process[$key][stripslashes($k)];
                } else {
                    $process[$key][stripslashes($k)] = stripslashes($v);
                }
            }
        }
        unset($process);
    }
    
    // Error reporting
    error_reporting(E_ALL ^ E_NOTICE);
    
	define("FAKE", 1);
	include("_cms/includes/global.php");
    
    StopWatch::Start();
    
	ObjMgr::Initialize();
    
    $pageid = (isset($_GET['page']) ? $_GET['page'] : Content::GetDefaultPageId());
    
    if (isset($_GET['logout']) && ObjMgr::GetAccount()->m_loggedin)
        ObjMgr::GetAccount()->Logout();
    
    if (isset($_GET['locale']))
        Locales::SetUserLocale($_GET['locale']);
    
    if (ObjMgr::GetAccount()->m_loggedin) {
        if (isset($_POST['action'])) {
            Compiler::$Mode = COMPILER_MODE_EDITOR;
            
            // Plugin Hook
            $data_object = new stdClass();
            $data_object->post = $_POST;
            ObjMgr::GetPluginMgr()->ExecuteHook("On_PostAction_" . $_POST['action'], $data_object);
            
            switch ($_POST['action']) {
				// Saves module data
                case 'module_update':
                    Editor::SaveModule($_POST['module_data']);
                    exit;
				// Returns module html
				case 'module_query':
					print Editor::GetModule($_POST['module_id']);
					exit;
				// Creates module template
				case 'module_create':
					print Editor::CreateModule($_POST['module_type'], $_POST['module_template'], $_POST['module_name']);
					exit;
				// Delte module
				case 'module_delete':
					print Editor::DeleteModule($_POST['module_id']);
					exit;
				// Saves page and module placing
                case 'page_update':
                    Editor::SavePage($_POST['page_data']);
                    exit;
				// Create new page
				case 'page_create':
					Editor::CreatePage($_POST['data']);
                    exit;
				// Delete page
				case 'page_delete':
					Editor::DeletePage($_POST['page_id']);
					exit;
				// Set default page
				case 'page_default':
					Editor::SetDefaultPage($_POST['page_id']);
					exit;
                default:
                    exit;
            }
        } else {
            print Editor::GetPage($pageid);
        }
    } else {
        $pagename = (isset($_GET['page']) ? $_GET['page'] : 'index');
        print Content::GetPage($pageid);
    }
    
    StopWatch::Stop();
    print("<br/><br/><h1>Execution took ". StopWatch::GetTotal() ."ms with " . Database::$queries . " queries</h1>");

	
	//print "<br/><br/>";
    /*
    if ($_GET['action'] == "login") {
        if (!$core->Account->m_loggedin) {
            if ($core->Account->Login($_GET['username'], $_GET['password'], false))
                print "Login successful!<br/>";
            else
                print "Login failed<br/>";
        } else {
            print "Your'e already logged in!<br/>";
        }
    }
    
    if ($_GET['action'] == "logout") {
        if ($core->Account->m_loggedin) {
            if ($core->Account->Logout())
                print "Logout successful!<br/>";
            else
                print "Logout failed<br/>";
        } else {
            print "Your'e not logged in!<br/>";
        }
    }
    
    
	date_default_timezone_set('UTC');
	//echo date( 'Y-m-d H:i:s');
	//print $core->Account->AddAccount("testas", "lolasjk", "lolasjk", "testas@lolas.lt", "testas@lolas.lt", GROUP_PLAYER);
	//print $core->Account->Login("testas", "lolas", false);
	//print_r($core->Account);
    //$core->Account->AddAccount("testas2", "lolasjk", "lolasjk", "testas@lolas.lt", "testas@lolas.lt", GROUP_PLAYER);
    print 'Logged in: ' . $core->Account->m_loggedin;*/
?>