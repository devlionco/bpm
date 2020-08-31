<?php

require_once("{$CFG->libdir}/formslib.php");
require_once('config.php');
 
class cert_download_form extends moodleform {
    function definition() {
        global $BU_CFG;

        $mform =& $this->_form;

        $ssd_url = str_replace('&amp;', '&', $this->_customdata['ssd_url']);
        $cert_url = str_replace('&amp;', '&', $this->_customdata['cert_url']);
        $course_type = $this->_customdata['course_type'];
        $cert_type = ($this->_customdata['cert_type']) ? $this->_customdata['cert_type'] : '';
        $branch = $this->_customdata['branch'];

        // Define certification type select element
        $cert_type_array;
        if ($course_type == $BU_CFG->COURSE_TYPES['program']) {
            $cert_type_array = ($cert_type != '') ? array($cert_type) : array('BPM');
        } else {
            $cert_type_array = ($cert_type != '') ? array('BPM', $cert_type) : array('BPM');
        }
        
        $cert_count = count($cert_type_array);
        if ($cert_count > 1) {
            $cert_types_html = "<select class=\"custom-select\" id=\"cert_type_lov\" name=\"סוג תעודה\">
                                <option disabled selected value>סוג תעודה</option>";
            for ($i=0; $i < count($cert_type_array); $i++) { 
                $cert_types_html .= "<option value=\"" . $cert_type_array[$i] . "\">" . $cert_type_array[$i] . "</option>";
            }
            $cert_types_html .= "</select><br>";
        } else {
            $cert_type = (($cert_type == 'ableton') || ($cert_type == 'cubase')) ? $cert_type : 'BPM';
            $cert_types_html = "<select class=\"custom-select\" id=\"cert_type_lov\" name=\"סוג תעודה\">
                                <option id=\"singleLovOption\" selected value>" . $cert_type . "</option></select><br>";
        }

        $mform->addElement('html', get_string('certtypechoose','block_bpm_utils'));
        $mform->addElement('html', $cert_types_html . '<br>');
        $mform->addElement('html', '<a id="cert_url" href="" style="display:none;">' . 
            get_string('certdownloadlink', 'block_bpm_utils') . '<br></a>');
        $mform->addElement('html', '<div id="example_text" style="display:none;">' . 
            get_string('certexampletext', 'block_bpm_utils') . '<br></div>');
        $mform->addElement('html', '<div id="ssd_text" style="display:none;"><br>' . 
            get_string('certssdtext', 'block_bpm_utils') . '<a id="ssd_url" href="">לינק הבא</a><br></div>');
        
        //temp - corona text
        /*$mform->addElement('html', '<div class="bpm_corona_update alert alert-danger"><strong>הנפקת תעודות בתקופת משבר הקורונה</strong><br>' . 
                            'לא ניתן כעת לאסוף תעודות, אך עדיין ניתן לשלוח בקשה להנפקה - התעודה תונפק ותגיע למכללה מיד לאחר החזרה לפעילות רגילה, ויישלח אליכם SMS עם הגעתה לצורך איסוף או תיאום השילוח.</div>');*/
        
        $mform->addElement('html', '<div id="example_image_text" style="display:none;">' . 
            get_string('certexampleimg', 'block_bpm_utils') . '</div>');
        $mform->addElement('html', '<img src="" id="example_image" style="max-width:300px;border-style:solid;border-width:1px;display:none;"></img>');
        $mform->disable_form_change_checker();

        $form_js = "<script type='text/javascript'>
                    var certTypeLov = document.getElementById(\"cert_type_lov\");
                    var certLink = document.getElementById(\"cert_url\");
                    var ssdLink = document.getElementById(\"ssd_url\");
                    var exampleText = document.getElementById(\"example_text\");
                    var exampleImg = document.getElementById(\"example_image\");
                    var ssdText = document.getElementById(\"ssd_text\");
                    var exampleImgText = document.getElementById(\"example_image_text\");
                    
                    function addEventHandler(elem, eventType, handler) {
                        if (elem.addEventListener)
                            elem.addEventListener (eventType, handler, false);
                        else if (elem.attachEvent)
                            elem.attachEvent ('on' + eventType, handler); 
                    }
                    
                    addEventHandler(certTypeLov, 'change', function() {
                        var cert_selected = this.value;
                        if  (cert_selected == '') {
                            cert_selected = document.getElementById(\"cert_type_lov\").children[0].innerText;
                        }
                        if (cert_selected != 'סוג תעודה') {
                            $(\"#cert_url\").show();
                            certUrl = \"" . $cert_url . "&certtype=\" + cert_selected;
                            certLink.href = encodeURI(certUrl);
                            ssdUrl = \"" . $ssd_url . "&certtype=\" + cert_selected;
                            ssdLink.href = encodeURI(ssdUrl);
                            certLink.style.display = \"block\";
                            exampleImg.style.display = \"block\";
                            exampleText.style.display = \"block\";
                            exampleImgText.style.display = \"block\";
                            ssdText.style.display = \"block\";
                            switch(cert_selected) {
                                case \"ableton\":
                                    exampleImg.src = \"assets/ableton_cert_sample.png\";
                                    break;
                                case \"cubase\":
                                    certLink.style.display = \"none\";
                                    exampleImg.style.display = \"none\";
                                    exampleText.style.display = \"none\";
                                    exampleImgText.style.display = \"none\";
                                    break;
                                case \"BPM\":
                                    exampleImg.src = \"assets/BPM_cert_sample.png\";
                                    break;
                                default:
                                    break;
                            } 
                        } else {
                            certLink.style.display = \"none\";
                            exampleImg.style.display = \"none\";
                            exampleText.style.display = \"none\";
                            exampleImgText.style.display = \"none\";
                            ssdText.style.display = \"none\";
                        }
                    });
                    addEventHandler(document, \"DOMContentLoaded\", function() {
                        var lov = document.getElementById(\"cert_type_lov\");
                        var event = new Event('change');
                        lov.dispatchEvent(event);
                    });
                       </script>";
        $mform->addElement('html', $form_js);
    }
}