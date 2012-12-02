<?
    // #########################
    // AJAX Communication Class
    // #########################
    
    class AJAXCommOPCodes {
        const CMSG_OPCODES_TABLE_REQUEST                    = 0; // Unused
        const SMSG_OPCODES_TABLE_RESPONSE                   = 1; // Unused
        const MSG_MULTIPLE_PACKETS                          = 2;
        const CMSG_MODULE_CREATE                            = 3;
        const SMSG_MODULE_CREATE_RESPONSE                   = 4;
        const CMSG_MODULE_DELETE                            = 5;
        const SMSG_MODULE_DELETE_RESPONSE                   = 6;
    }

    if (!FAKE)
        exit;
    
    class AJAXComm {
        // Receiving Packets
        
        public static function HandlePacket($packet) {
            switch($packet->opcode) {
                case AJAXCommOPCodes::CMSG_OPCODES_TABLE_REQUEST:
                    break;
                case AJAXCommOPCodes::MSG_MULTIPLE_PACKETS:
                    foreach($packet->data->packets as $m_packet) {
                        self::HandlePacket($m_packet->opcode, $m_packet->data);
                    }
                    break;
                case AJAXCommOPCodes::CMSG_MODULE_CREATE:
                    self::HandleModuleCreate($packet);
            }
        }
        
        // Sending Packets
        
        public static $send_queue = array();
        
        public static function QueuePacket($opcode, $data, $target = -1) {
            $packet = new stdClass();
            $packet->opcode = $opcode;
            $packet->target = $target;
            $packet->data = $data;
            self::$send_queue[] = $packet;
        }
        
        public static function BuildPacketQueue() {
            // No packets are queued
            if (count(self::$send_queue) == 0)
                return json_encode(null);
            
            // Only one packet is queued
            if (count(self::$send_queue) == 1)
                return json_encode(self::$send_queue[0]);
            
            // Multiple packets are queued
            $data = new stdClass();
            $packet->opcode = AJAXCommOPCodes::MSG_MULTIPLE_PACKETS;
            $packet->data = new stdClass();
            $packet->data->packets = self::$send_queue;
            return json_encode($packet);
        }
        
        // HANDLERS
        
        public static function HandleModuleCreate($packet) {
            $data = $packet->data;
            
            $response_data = new stdClass();
            
			if (!is_file(COMPILER_TEMPLATES_DIR . "/modules/" . $data->type . "/" . $data->template . ".tmpl")) {
                // LOG NEEDED
				//die("Editor::CreateModule: Template not found: " . $data->type . ":" . $data->template);
                $response_data->success = false;
			} else {
                $type = Database::Escape($data->type);
                $template = Database::Escape($data->template);
                $name = Database::Escape($data->name);
                
                Database::Query("INSERT INTO `" . DB_TBL_MODULE_TEMPLATE . "` (`type`, `template`, `name`) VALUES ('" . $type . "', '" . $template . "', '" . $name . "')");
                $id = Database::GetLastIncrId();
                
                $module_data = Editor::GenerateModuleData($id);
                
                $response_data->success = true;
                $response_data->module_data = $module_data;
            } 
			
			self::QueuePacket(AJAXCommOPCodes::SMSG_MODULE_CREATE_RESPONSE, $response_data);
		}
    }
    
?>