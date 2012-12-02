CMS.Comm = {
    Init: function() {
        // Copy opcodes locally
        this.OPCodes = CMS.Data.Defines.OPCodes;
        
        // Ready!
        CMS.OutDebug("CMS.Comm.Init(): Loaded!", CMS.Debug.JS_COMM);
    },
    
    send_queue: new Array(),
    callback_stack: new Array(),
    
    QueuePacket: function(opcode, data, callback = false) {
        var packet = new Object();
        packet.opcode = opcode;
        packet.id = CMS.GenUniqueID();
        packet.data = data;
        this.send_queue.push(packet);
        
        if (callback)
            this.callback_stack[packet.id] = callback;
    },
    
    Send: function() {
        if (this.send_queue.length == 0)
            return CMS.OutError("CMS.Comm.Send(): Packet queue is empty!");
        
        var request = new Object();
        if (this.send_queue.length == 1)
            request = this.send_queue[0];
        else {
            request.opcode = CMS.Comm.OPCodes.MSG_MULTIPLE_PACKETS;
            request.id = CMS.GenUniqueID();
            request.data = new Object();
            request.data.packets = this.send_queue;
        }
        
        $.ajax({
			type: "POST",
			url: CMS.Data.Defines.AJAX_URL,
			data: { mode: 'ajax', data: request },
		}).done(function(response) {
            CMS.Comm.HandlePacket(response);
        });
    },
    
    HandlePacket: function(packet) {
        switch (packet.opcode) {
            case CMS.Comm.OPCodes.MSG_MULTIPLE_PACKETS:
                for (var m_packet in packet.data.packets) {
                    this.HandlePacket(m_packet.opcode, m_packet.data);
                }
                break;
            default:
                CMS.OutError("CMS.Comm.HandlePacket(): Received unknown opcode '" + opcode + "'");
        }
    }
};

