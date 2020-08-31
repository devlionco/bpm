<?php

$templates = json_decode(file_get_contents('templates.json'));

if (isset($_POST['content'])) {
    
    $content = $_POST['content'];
    $parent = $_POST['parent'];
    $child = $_POST['child'];
    $templates->$parent->$child = $content;
    //print_r($templates);
    //echo "<br>********<br>";
    //print_r(json_encode($templates));
    //print_r($templates->$_POST['parent']->$_POST['child']);
    if (file_put_contents('templates.json', json_encode($templates, JSON_PRETTY_PRINT))) {
        echo 'all good';
    } else {
        echo 'error';
    }
    return false;
} else {
    prepare_edit_interface($templates);
}

function prepare_edit_interface($templates) {
    
    //echo "<pre>";
    
    //print_r($templates);
    echo "<script>let templates = {};</script>";
    foreach($templates as $key => $value) {
        echo "<script>templates." . $key . " = {};</script>";
        //print_r($key);
        
        //print_r(":<br>");
        foreach($value as $inner_key => $inner_val) {
             echo "<script>templates." . $key . "." . $inner_key . " = " . json_encode($inner_val) . ";</script>";
             //print_r($inner_key);    
        }
        
    }
    echo "<script>console.log('templates', templates)</script>";
    //echo "</pre>";
}
?>

<!DOCTYPE html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1 user-scalable=1">
<meta charset="utf-8" /> 
<link rel="stylesheet" href="edit.css">
<link rel="icon" 
     type="image/png"
 href="https://my.bpm-music.com/local/BPM_pix/ak_favicon(5).ico">
 <title>הודעות פתיחה/סיום לקורסים</title>
 <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
 <script src="tinymce/js/tinymce/tinymce.min.js"></script>
</head>
<body>
<div id="main">
    <h1>הודעות פתיחה/סיום לקורסים</h1>
    <h2>בחר תבנית לעריכה:</h2>
    <div>
        <ul id="templatesList">
            
        </ul>
        <div id="editorContainer">
		<h2 id="editorHeader">עריכת תבנית הודעה: <span id="currentTemplateName"></span></h2>
        <textarea id="content">
            
        </textarea>
        <button type="button" id="save">שמור</button>
        </div>
    </div>
</div>
<script>
let html = '',
    currentItem;
Object.keys(templates).forEach(function(item, index) {
     console.log('item', item);
    // console.log(templates[item]);
    html += "<details><summary>" + translate(item) + "</summary><ul>";
    Object.keys(templates[item]).forEach(function(innerItem, innerIndex) {
        html += "<li class='inner'>" + 
                "<a href='#' data-parent='" + item + "' data-child='" + innerItem +  "'>" + translate(innerItem) + "</a></li>";
    });
    html += "</details>";
});
html += "</li>";
$('#templatesList').append(html);

$(document).ready(function() {
    tinymce.init({
        selector: 'textarea',
        plugins: ["link", "code", "lists", "directionality"],
        menubar: false, 
        toolbar: 'undo redo | styleselect |   bold italic underline | link code | bullist | rtl ltr | alignleft aligncenter alignright alignjustify',
        height : "480"
    });
    
    $('a').click(function(){
        let parent = $(this).data('parent'),
            child = $(this).data('child');
        let content = templates[parent][child];
        //console.log(content);
        //$('textarea').val(content)
        tinymce.activeEditor.execCommand('mceSetContent', false, content);
        currentItem = [parent, child];
        $('#editorContainer').slideDown(200);
        console.log('child', child);
        console.log('parent', parent);
		$('#currentTemplateName').text(translate(child) + " - " + translate(parent));
    });
    
    $('#save').click(function() {
        if (typeof currentItem == 'undefined') {
            alert('לא נבחרה תבנית');
            
            return false;   
        }
        
        console.log('currentItem:', currentItem);
        console.log('templates[currentItem[0]][currentItem[1]]:', templates[currentItem[0]][currentItem[1]]);
        let postObj = {
            parent: currentItem[0],
            child: currentItem[1],
            content: tinymce.activeEditor.getContent({format: 'raw'})//templates[currentItem[0]][currentItem[1]]
        }
        console.log('postObj:', postObj);
        $.post('edit_template.php', postObj, function(data) {
            console.log('data', data);
            switch (data) {
                case 'all good':
                    message = 'התבנית נשמרה בהצלחה';
                    break;
                case 'error':
                    message = 'שגיאה בשמירת התבנית - פנה למנהל המערכת';
                    break;
            }
            
            tinymce.activeEditor.execCommand('mceSetContent', false, '');
            currentItem = undefined;
			$('#currentTemplateName').text('');
			$('#editorContainer').slideUp(200);
            
            alert(message);
            console.log(data);
             setTimeout(function(){
                  window.location.reload();
            });
			
            
        });
    });
    
});

function translate(input) {
    // console.log('translate ,input: ', input);
    let branch = '';
    if (input.includes("Haifa")) {
        branch = " - חיפה";
    }
    input = input.replace("_Haifa", "");
    switch (input) {
        case 'radio_voiceover_logic_scratch':
            output = "רדיו/קריינות/לוג'יק/סקראץ";
            break;
        case 'other':
            output = 'שאר הקורסים';
            break;
        case 'start':
            output = 'הודעת פתיחה';
            break;
        case 'end':
            output = 'הודעת סיום';
            break;
        case 'online':
            output = 'אונליין';
            break;
        default:
            output = input;
    }
    return output + branch;
    
}
</script> 

</body>
</html>