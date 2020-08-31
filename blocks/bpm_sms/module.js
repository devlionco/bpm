// JavaScript Document

M.block_bpm_sms = {};
M.block_bpm_sms.init = function(Y,param3) {

    // Variables.
    var showuser= Y.one("#btnajax");
    var bpm_sms_send = Y.one('#bpm_smssend');
    var action = Y.one('#id_r_id');
    var action1 = Y.one('#id_m_id');
    var action2 = Y.one('#id_c_id');
    var userlist= Y.one("#table-change");
    var img=Y.one('#load');

    // Body load first time
    var msg_body = Y.one('#id_bpm_sms_body');
    
    var m_id=action1.get('value');
            Y.io('load_message.php?m_id='+m_id, {
                on: { 
                    start: function(id, args) {
                        msg_body.hide();
                        img.show();
                        
                    },
                    complete: function(id, e) {
                        var json = e.responseText;
                        console.log(json);
                        img.hide();
                        msg_body.show();
                        msg_body.set('value', json);
                    }
                 }
          });

    // Image default setting.
    img.hide();
    bpm_sms_send.hide();
    require(['jquery', 'jqueryui'], function($, jqueryui) {
		$(document).ready(function() {
			$('#btnajax').click();
			 $('#bpm_smssend').on('click',function(){
			 		document.getElementById('id_bpm_sms_body_hidden').value = document.getElementById('id_bpm_sms_body').value;
			 });
		});
	});

    // Event occurs after click on show user button.
    showuser.on('click',function() {
        var content = Y.one('#id_bpm_sms_body');
        var c_id=action2.get('value');
        var r_id=action.get('value');
        var msg = content.get('value');

        Y.io('user_list.php?msg='+msg+'&c_id='+c_id+'&r_id='+r_id, {
            on: {
                start: function(id, args) {
                    userlist.set('innerHTML','<img src="Loading.gif" id="load-users" style="margin-left:6cm;" />');
                },
                complete: function(id, e) {
                    var json = e.responseText;
                    console.log(json);
                    userlist.set('innerHTML', json);
                    bpm_sms_send.show();
                    }
                }
          });
    });
    // End show user event.

    // If viewpage is 2 means send bpm_sms page.
    if(param3 == '2') {
        action.on('change', function() {
            var b=this.get('text');
        });

        // Select Message Template.
        action1.on('change', function() {
            var content = Y.one('#id_bpm_sms_body');
            var m_id=action1.get('value');
            Y.io('load_message.php?m_id='+m_id, {
                on: {
                    start: function(id, args) {
                        content.hide();
                        img.show();
                    },
                    complete: function(id, e) {
                        var json = e.responseText;
                        console.log(json);
                        img.hide();
                        content.show();
                        content.set('value', json);
			require(['jquery', 'jqueryui'], function($, jqueryui) {
	                        $('#btnajax').click();
        	        });

                    }
                 }
          });
       });
    }
};
