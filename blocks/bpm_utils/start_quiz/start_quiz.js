quizmodules = document.currentScript.getAttribute('quizmodules');
quizmodules = decodeURIComponent(quizmodules).replace(/\+/g, " ");
quizmodules = JSON.parse(quizmodules);

// console.log('quiz_modules', quizmodules);


const quizModalHtml = '\
  <button type="button" class="btn btn-primary" data-toggle="modal" data-target="quizModal" onclick="openquizModal()" style="display:block;margin-bottom: 5px;">\
    נהל מבחנים</button>\
  <div class="modal" id="quizSettingsModal" style="z-index:100001">    <div class="modal-dialog">      <div class="modal-content">\
        <div class="modal-header">          <h4 class="modal-title">נהל מבחנים</h4>          <button type="button" class="close" onclick="closequizModal()" data-dismiss="modal">&times;</button></div>\
        <div class="modal-body">\
        </div>\
        <div class="modal-footer">\
          <button type="button" class="btn btn-danger" onclick="closequizModal()" data-dismiss="modal">סגור</button>\
        </div>\
      </div>\
    </div>\
  </div>' +
  "<div id='quizModalOverlay' style='display:none;background-color: rgba(0, 0, 0, 0.73); position: fixed; width: 100%; height: 100%; z-index: 100000; top: 0px; right: 0px; cursor: pointer;'></div>";
  addquizInputModal(quizmodules);
  
  const style=getStyle('https://my.bpm-music.com/blocks/bpm_utils/start_quiz/manage_quizzes.css');

function getStyle(url) {
    $.get(url, function(data) {
        $('head').append("<style>" + data + "</style>");
    });
}

function addquizInputModal(quizmodules) {
    $('#section-0 .content ul').first().prepend(quizModalHtml);
        $('#quizSettingsModal').data('quizmodules', quizmodules);
        $('#quizSettingsModal')[0].addEventListener("keydown", function(e) {
            if (e.keyCode === 27) { // esc
                e.preventDefault();
                e.stopPropagation();
                closequizModal();
            }
        });
        initQuizmodalContent(quizmodules);
}

function initQuizmodalContent(quizModules) {
    outputHtml = "<table id='quizManager'><thead><tr>" +
                    "<th>שם</th><th>מצב</th><th colspan='2'>פעולה</th></tr></thead><tbody>";
    Object.keys(quizModules).forEach(function(item, index) {
        quiz = quizModules[item];
        
        quizHtml = "<tr class='quiz_manage_row'><td>" + quiz.name +
                    "</td>" + availibility(quiz.visible, quiz.timeclose, quiz.cmid) + "<td class='quizAction'>" +
                    actionBtn(quiz) +
                    
                    "</td>";
        outputHtml += quizHtml;
        
    })
    outputHtml += "</tbody></table>";
    // console.log(outputHtml);
    $('#quizSettingsModal').find('.modal-body').append(outputHtml);
}

function actionBtn(quiz) {
    if (quiz.timeclose ==0 && quiz.visible == 0) {
        html = "<button title='חשוף את המבחן והגדר תפוגה למשך שעתיים מעכשיו' class='startQuiz' onclick=\"startQuiz(\'" + quiz.id + "\')\">" + 
                "התחל מבחן" + "</button>";
    } else {
        editUrl = "https://my.bpm-music.com/course/modedit.php?update=" + quiz.cmid
        html = "<a href='" + editUrl + "'>נהל</a>";
    }
    return html;    
}


function availibility(visibility, timeClose, cmid) {
    console.log('availability: visibility', visibility, 'timeclose: ', timeClose, 'cmid: ', cmid);
    visibilityText = (visibility == 1) ? "גלוי" : "נסתר";
    timeCloseText = (timeClose == 0) ? "אין תפוגה" : "זמינות: " + bpmFormatTime(timeClose);
    timeNow = Math.round(new Date().getTime()/1000);
    let comment, color;
    if (visibility == 1 && timeClose == 0) {
        comment = "יש צורך בהגדרת תפוגה כדי למנוע ניסיונות מענה לא מבוקרים";
        color = "red";
    } else if (visibility == 1 && timeClose < timeNow) {
        overridesUrl = "https://my.bpm-music.com/mod/quiz/overrides.php?cmid=" + cmid + "&mode=user";
        comment = "פג תוקף לניסיונות מענה חדשים" + "<br> <a href='" + overridesUrl + "'>קבע זמינות עבור סטודנט ספציפי</a>";
        color = "inherit";
    } else if (visibility == 0 && timeClose > 0) {
        comment = "הוגדר תוקף אך המבחן נסתר - ודא את הגדרות המבחן";
        color = "red";
    } else if (visibility == 1 && timeClose > timeNow){
        comment = "המבחן זמין לנסיונות מענה";
        color = "inherit";
    } else {
        comment = "לחץ על הכפתור כדי לחשוף את המבחן ולקבוע עבורו תפוגה (שעתיים מרגע ההתחלה)";
        color = "inherit";
    }
    
    html = "<td class='quiz_availability_status'>" + visibilityText + ", <br>" + timeCloseText + "</td>";
    // console.log('comment:', comment)
    if (comment) {
        html +='<td style="color:' + color + ';">' + comment + '</td>';
    }
    // console.log(html);
    return html;
}



function bpmFormatTime(input) {
  var a = new Date(input * 1000);
  var year = a.getFullYear();
  var month = a.getMonth() + 1;
  var date = a.getDate();
  var hour = (a.getHours() > 9) ? a.getHours() : "0" + a.getHours();
  var min = (a.getMinutes() > 9) ? a.getMinutes() : "0" + a.getMinutes();
  
  var time = date + '/' + month + '/' + year + ' ' + hour + ':' + min;

    return time;
}

function openquizModal() {
    $('#quizModalOverlay').fadeIn(300, function(){
        $('#quizSettingsModal').fadeIn(300, function() {
            $('#quizUrlInput').focus();
        });
    }).click(function(){closequizModal();});
    
    //close modal on overlay click
    $('#quizSettingsModal').click(function(e){
        if ($(e.target).attr('id') == 'quizSettingsModal') {
            closequizModal();
        }
    });
}
function startQuiz(quizId) {

    postObj = {
        quizId: quizId,
        courseid: document.currentScript.getAttribute('cid')
    }
    
    postUrl = 'https://my.bpm-music.com/blocks/bpm_utils/start_quiz/start_quiz.php';
    $.post(postUrl, postObj, function(data) {
        // console.log(data);
        if (data != 'all good') {
            
            closequizModal();
        } else {
            existingContent = $('#quizSettingsModal .modal-body').html();
            // console.log(existingContent);
            $('#quizSettingsModal .modal-body').html('<h3>הצלחה!</h3>')
            setTimeout(function(){
                closequizModal();
                window.location.reload();
            }, 600);
            setTimeout(function(){
                closequizModal();
                $('#quizSettingsModal .modal-body').html(existingContent)
                //reassign data attributes because they were added dynamically
                $('#quizUrlInput').data('courseid', courseid);
            }, 1400);
        
            
        }
    });
}
function closequizModal() {
    $('#quizSettingsModal').hide();
    $('#quizModalOverlay').fadeOut(300);
}