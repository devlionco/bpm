$(document).ready(function() {
    if (window.bob && Object.keys(bob).length > 0) {
        arrangeSessions(bob);
        applyCollapseOption($('.courseNameRow').length);
        applyTruncationOption();
        additionalDatePickerListeners();
    } else {
        error();
    }
});

function arrangeSessions(inputObj) {
    let title = inputObj[Object.keys(inputObj)[0]].student_name;
    // console.log(title);
    
    $('#sessions tbody').empty();
        let resultArr = Object.values(inputObj),
         courseArr = Object.values(resultArr.reduce((output, {
            shortname,
            sessdate,
            courseid,
            id,
           description, 
           student_name,
           enrollment_status,
           log
        }) => {
            //Create new group
            if (!output[shortname]) output[shortname] = {
                shortname,
                courseid,
                enrollment_status,
                sessions: []
            };
            // Append to group
            output[shortname].sessions.push({
                sessdate,
                id,
                description,
                log
            });
            return output;
        }, {}));
        // console.log('courseArr: ', courseArr);
        courseArr.forEach(function(item, index) {
            courseSessionRows = '',
            courseLogsCount = 0,
            coursePointsCount = 0,
            courseMaxPoints = 0;
            sessionsCount = 1;
            item.sessions.forEach(function(entry, entryIndex) {
                //console.log(entry);
                //console.log(entry.description);
                let logStatus = getLogClass(entry.log, 'status'),
                    logPoints = getLogClass(entry.log, 'points');
                    // console.log(logPoints.innerHTML);
                    if (logPoints.innerHTML != '-') {
                        // console.log('coursePointsCount before', coursePointsCount);
                        coursePointsCount += parseInt(logPoints.innerHTML);
                        courseMaxPoints += 2
                        // console.log('coursePointsCount after', coursePointsCount);
                        logPoints.innerHTML += '/2';
                        courseLogsCount++;
                    }
                courseSessionRows += '<tr class="sessionRow" data-courseid="' + item.courseid + 
                                    '" data-sessdate="' + entry.sessdate + '"><td class="sessionsCount">' + sessionsCount + '</td><td class="sessDate">' + reformatDateToHuman(reformatUnixtimeToHuman(entry.sessdate)) + 
                                    '</td><td class="sessDescription"><div class="innerDescriptionBox">' + fixHtml(entry.description) + 
                                    '</div></td><td class="logStatus">' + logStatus.outerHTML + '</td>' + 
                                    '<td class="sessPoints">' + logPoints.outerHTML + '</td></tr>';
                sessionsCount++;
            }); 
         
            courseAttPercent = parseFloat(coursePointsCount/courseMaxPoints * 100).toFixed(1);
            if (isNaN(courseAttPercent)) {
                courseAttPercent = "<span class='noAttPercent'>X</span>";
            } else {
                courseAttPercent += '%';
            }
            console.log(item);
            enrollmentLED = {
                statusClassName: (item.enrollment_status == "0") ? 'on' : 'off', 
                title: (item.enrollment_status == "0") ? 'הרשמה מושהית - קורס לא זמין לסטודנט': 'קורס זמין לסטודנט',
                light: function() { 
                        return $('<i/>', {
                        class: 'enrollmentLED fas fa-circle ' + this.statusClassName,
                        title: this.title
                        })
                }
            }
            
            console.log(enrollmentLED);
            courseDiv = $('<tbody/>', {
                class: 'courseTable'}).html(
                '<tr class="courseNameRow course_' + index + 
                '" data-courseid="' + item.courseid + '"><td class="courseName" colspan="5">' + 
                enrollmentLED.light()[0].outerHTML + '<span class="actualCourseName">' +item.shortname + '</span><span class="separator"> | </span>' +
                 '<span class="attendancePercent">אחוז נוכחות: <span>' +  courseAttPercent + '</span></span><span class="separator"> | </span>' +
                '<span class="sessionsCount">(רישום של ' + courseLogsCount + ' מתוך ' +
                item.sessions.length  + ' מפגשים)</span>' + '</td><td class="hideCourse">' + 
                '<button type="button" class="hideCourseBtn dontPrint" onclick="hideCourse(this)" title="הסתר קורס זה"><i class="fas fa-eye-slash"></i></button></td></tr>'
            );
            courseDiv.append(courseSessionRows);
            $('#sessions').append(courseDiv);
        });
        
        //collapse/expand course rows
        $('.courseName').click(function() {
            if ($('input[name="dateType"]:checked').val() == 'all') {
                
                $(this).parents('tr').nextUntil('.courseNameRow').slideToggle(300);
            } else {
                $(this).parents('tr').nextUntil('.courseNameRow').show();
            }
        });
        $('#subtitle').html("<span class='bold'>שם הסטודנט:</span> " + title);
        renderDatePickers();
        $('.hasDatePicker').prop('disabled', 'true');
        
}

function getLogClass(input, outputType) {
    switch (input) {
        case 'נוכח':
            className = "present";
            label = input;
            points = 2
            break;
        case 'איחר':
            className = "late";
            label = input;
            points = 1;
            break;
        case 'נעדר':
            className = "absent";
            label = input;
            points = 0;
            break;
        case 'מאושר':
            className = "dismissed";
            label = input;
            points = 2;
            break;
        default:
            className = "noLog";
            label = "-אין רישום-";
            points = '-';
    }
    switch (outputType) {
        case 'status':
            break;
        case 'points':
            className = '';
            label = points;
            break;
            
    }
    output = $('<span/>', {
        class: className,
        text: label
    })[0];
    return output;
}

function countInArray(array, item) {
    var count = 0;
    for (var i = 0; i < array.length; i++) {
        if (array[i] === item) {
            count++;
        }
    }
    return count;
}

function validateFileType(filename) {
    const allowedTypes = ["pdf"];//["png", "jpg", "jpeg", "pdf"];    
    if (allowedTypes.indexOf(getFileExtension(filename)) > -1) {
        return true;
    } else {
        return false;
    }
}


function validateDate(input) {
    var regex = new RegExp("[0-9]{1,2}[/|\/]{1}[0-9]{1,2}[/|\/]{1}[0-9]{2}?");
    if (regex.test(input) && input.length == 8 || input == '0') {
        return true;
    } else {
        return false;
    }
}

function reformatUnixtimeToHuman(input) {
    date = new Date(input*1000);
    day = doubleDigit(date.getDate());
    month = doubleDigit(date.getMonth() + 1);
    //console.log(day);
    return date.getFullYear() + '-' + month + '-' + day;
}

function doubleDigit(input) {
    if (input.toString().length == 1) {
        return "0" + input;
    } else {
        return input
    }
}

//input: dd/mm/yy string
//output: yyyy-mm-dd
function reformatDateToISO(input) {
    var dateSplit = input.split("/");
    var newDate = new Date('20' + dateSplit[2], dateSplit[1]-1, dateSplit[0]);
    return newDate;
}

function error() {
    $('<span/>', {style: 'color:red;font-style:italic'}).text('אין נתונים. במידה והינך חושד שמדובר בשגיאה או תקלה פנה למנהל המערכת.').appendTo('#subtitle');
    $('#dateBox, #showDescriptionsBox, #descriptiontruncationBox, ' + 
    '#sessions, #bpm_main h1, #profilePic').remove();
}

//input: yyyy-mm-dd / full date string
//output: dd/mm/yy string
function reformatDateToHuman(input) {
    var input = new Date(input);
    var dd = input.getDate();
    var mm = input.getMonth() + 1; 
    var yyyy = input.getFullYear();
    if(dd < 10) {
        dd = '0' + dd;
    } 
    if(mm < 10){
        mm = '0' + mm;
    }  
    return dd + '/' + mm + '/' + (yyyy.toString().substr(-2));
}

//when finished - converts the input to type=text so that it doesn't get picked up next time the function runs
function renderDatePickers() {
    $( function() {
        $( "input[type=date]" ).datepicker({
            dateFormat: "dd/mm/y"
        }).change(function(){
            if (!validateDate($(this).val())) {
                $(this).val('');
                $(this).addClass('validationError');
                $(this).after('<span class="tooltip" >יש להזין תאריך תקין, בפורמט dd/mm/yy</span>');
            } else {
                $(this).removeClass('validationError');
                $(this).next('span.tooltip').remove();
            }
        }).click(function(){
            $(this).next('span.tooltip').remove();
            $(this).removeClass('validationError');
        }).keyup(function(e) {
            if(e.keyCode == 8 || e.keyCode == 46) {
                $.datepicker._clearDate(this);
            }
        }).each(function(){
            $(this).attr('type', 'text');
            if ($(this).val() == '1970-01-01') {
                $(this).val('0');
            } else {
                $(this).datepicker('setDate', new Date($(this).val()))
            }
        });
        //$( "input[type=date]" ).attr('type', 'text');
    });
}


function dateToTimestamp(input) {
    input = reformatDateToISO(input);
    const timestamp = new Date(input).getTime() / 1000;
    // console.log(timestamp);
    return timestamp
}

//called by onchange on datepicker fields && radio selectors
function applyDateFilter(triggerElement) {
    console.log('applyDateFiler, trigEl is ');
    console.log(triggerElement);
    let radioChoice = $('input[name="dateType"]:checked');
    let dateFrom = $('#dateFrom'),
        dateTo = $('#dateTo');
    //  console.log(radioChoice.val());
    if (radioChoice.val() == 'all') {
        dateFrom.prop('disabled', true);
        dateTo.prop('disabled', true);
        $('.courseNameRow, .sessionRow').show();
        applyCollapseOption($('.courseNameRow:visible').length);
        return false;
    } else {
        // console.log('disabled = false')
        dateFrom.prop('disabled', false);
        dateTo.prop('disabled', false);
    }
    today = whatsToday(); // current dd/mm/yy
    if (dateFrom.val() == today && dateFrom != triggerElement) {
        dateFrom.focus();
        return false;
    } else if (dateTo.val() == today && dateTo != triggerElement) {
        setTimeout(function(){$('#dateTo').focus()},0);
        return false;
    } else {
        unixDateFrom = new Date(reformatDateToISO(dateFrom.val())).getTime() / 1000;
        unixDateTo = new Date(reformatDateToISO(dateTo.val())).getTime() / 1000;
        $('.courseNameRow').each(function(){
            $(this).nextUntil('.courseNameRow').each(function(){
                if ($(this).data('sessdate') >= unixDateFrom && $(this).data('sessdate') <= unixDateTo) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
            // console.log("$(this).nextUntil('.courseNameRow').is('visible')", $(this).nextUntil('.courseNameRow').is('visible'));
            //get data key with courseid, make sure no course titles stay visible with no sessions
            let courseid = $(this).attr('data-courseid');
            if ($('.sessionRow[data-courseid="' + courseid + '"]:visible').length > 0) {
                $(this).show();
            } else {
                $(this).hide()
            }
        });
        
    }
     applyCollapseOption($('.courseNameRow:visible').length);
    
}

function whatsToday() {
    let today = new Date();
    dd = String(today.getDate()).padStart(2, '0'),
    mm = String(today.getMonth() + 1).padStart(2, '0'), //January is 0!
    yy = today.getFullYear().toString().substr(2, 2);

    output = dd + '/' + mm + '/' + yy;
    return output;
}

function applyCollapseOption(courseCount) {
    if (courseCount < 6) {
        $('#collapseCourses').remove();
        return false;
    } else if ($('#collapseCourses').length > 0) {
        return false;
    }
    span = $('<span/>', {
        id: 'collapseLink',
        class: 'dontPrint',
        href: '#',
        onclick: "$('.sessionRow').slideUp(300)"
    }).text('קפל את תצוגת הקורסים');
    p = $('<p/>', {
        id: 'collapseCourses',
    }).html('מציג ' +  courseCount+ ' קורסים.  ' + $(span)[0].outerHTML);
    p.appendTo('#bpm_ui_container');
}

function applyTruncationOption() {
    let flag = false;
    block: for (let i = 0; i < $('.innerDescriptionBox').length; i++) {
        console.log($('.innerDescriptionBox').eq(i).html());
        console.log($('.innerDescriptionBox').eq(i).text().length);
        console.log(checkforLineBreaks($('.innerDescriptionBox').eq(i)));
        if ($('.innerDescriptionBox').eq(i).text().length > 86  || checkforLineBreaks($('.innerDescriptionBox').eq(i))) {
            flag = true;
            break block
        }
    }
    
    if (!flag)  $('.descriptiontruncationBox').remove();
}

function checkforLineBreaks(input) {
    let regex = new RegExp("(<li>)*|(<br>)*|(\\n)*"); 
    if(regex.test(input)) 
        return true;
        return false;
    
}
    


function hideCourse(el) {
    row = $(el).parentsUntil('tr').parent();
    row.nextUntil('.courseNameRow').hide();
    row.hide();
    applyShowAllBtn();
}

function applyShowAllBtn() {
    if ($('#showAllBtn').length > 0) {
        return false;
    }
    btn = $('<button/>', {
        type: 'button',
        onclick: "$('.courseNameRow, .sessionRow').show();$(this).remove();",
        id: 'showAllBtn',
        class: 'dontPrint',
        text: 'הצג את כל הקורסים'
    }).appendTo('#bpm_ui_container');
}

function fixHtml(html) {
    let div = document.createElement('div'),
        regex = /<br\s*[\/]?>/gi;
    html = html.replace(regex, "\n");
    div.innerHTML = html;
    
    return (div.innerHTML);
}

function truncateDescriptions(el) {
    boolAction = $(el).parent().find('input[type="checkbox"]:checked').length > 0;
    if (boolAction) {
        $('.sessDescription').each(function(){
            if ($(this).text().length > 87 || $(this).html().indexOf("<p>") >= 0 || $(this).html().indexOf("<li>") >= 0)
            $(this).addClass('truncatedDescription');
        });
    } else {
        $('.truncatedDescription').removeClass('truncatedDescription');
    }
}

function additionalDatePickerListeners() {
    $('.hasDatePicker').each(function(){
        element = $(this)[0];    
        ['keyup','blur'].forEach( evt => 
            element.addEventListener(evt, 
                function(){applyDateFilter(this)},
                false
            )
        );
    });
}



function bpm_print(element) {
    //truncated feature made availble but not used - will result in >2x pages
    if ($('#descriptiontruncationBox').length > 0 &&
        $('.courseNameRow:visible').length > 1 &&
        ($('.truncatedDescription').length == 0 && 
            $('#showDescriptionsBox').find('input:checked').length > 0)
        ) {
        $('#showDescriptionsBox').find('input').prop('checked', false).trigger('change');
    }
    element = element.parent();
    copyOf = element.clone(true);
    console.log(copyOf);
    copyOf.find('.dontPrint').remove();
    copyOf.find('#bpm_main').addClass('nonEmbed');
    if ($('input[name="dateType"]:checked').val() == 'all') {
        copyOf.find('input#dateTo, input#dateFrom, span#dateBetween, label[for="dateType_choose"]').remove();
    }
    copyOf.find('#bpm_main').addClass('printReady');
    copyOf.find('.attendancePercent').css('display', 'inline !important');
    copyOf.find('.sessionsCount').remove();
    
    
    footer = '<div id="footer"><img src="https://my.bpm-music.com/local/BPM_pix/footer2018/footer_black.png" width="700"/></div>'
    var win = window.open("", "דוח נוכחות מפורט",
                            "toolbar=no,location=no,directories=no,status=no,menubar=no,\
                            scrollbars=yes,resizable=yes,width=600,height=900,top="+(screen.height-400)+",left="+(screen.width-840));
   
   htmlPrintTableTemplate = bpm_printTableLayoutTemplate(null, copyOf.html(), footer);
    $.get("style.css").done(function(response) {
        $('<style />').text(response).appendTo($(win.document.head));
        win.document.body.innerHTML =htmlPrintTableTemplate;
        win.window.focus();
        win.window.print();
    
    });
    
    //alt method, print element
    // var prtContent = copyOf[0]
    // var WinPrint = window.open('', '', 'left=0,top=0,width=800,height=900,toolbar=0,scrollbars=0,status=0');
    // WinPrint.document.write(prtContent.innerHTML);
    // WinPrint.document.close();
    // WinPrint.focus();
    // WinPrint.print();
    
    
    // WinPrint.close();
    
}

function bpm_printTableLayoutTemplate(header, content, footer) {
    header = header || $("<h1/>", {class:'print_headerFirstPage'}).text(document.title)[0].outerHTML;
     console.log(header);
    let jqHtml = $('<table> <thead><tr><td><div class="header-space">' + header + '</div>\
                    </td></tr></thead><tbody><tr><td> <div class="content"></div>\
                    </td></tr></tbody><tfoot><tr><td><div class="footer-space">' + footer + '</div>\
                    </td></tr></tfoot></table><div class="header">' + header + '</div>\
                    <div class="footer">' + footer + '</div>');
    jqHtml.find('.content').html(content);
    jqHtml.find('#footer').css('display', 'block !important');
    // jqHtml.find('#datebox').html($('#datebox').text());
    logo = jqHtml.find('#logo')
    printLogo = logo.clone();
    logo.hide();
    console.log(printLogo);
    printLogo.css('display', 'block!important;').addClass('printLogo')
    printLogo.appendTo(jqHtml.find('.header-space'));
    
    jqHtml.find('.courseName').each(function(){
        $(this).find('.separator').remove();
        $(this).parent().find('span:not(".attendancePercent, .attendancePercent *")').css('display', 'block');
    });
    
    return jqHtml[0].outerHTML;
}

function toggleSessDescriptions(el) {
    
    boolAction = $(el).parent().find('input#' + el.getAttribute('id') + '[type="checkbox"]:checked').length > 0;
    if (boolAction) {
        $('th#sessDescription, td.sessDescription').show();
        $('.courseName').attr('colspan', '5')
        $('input#truncateDescriptions').prop('disabled', false)
    } else {
        $('th#sessDescription, td.sessDescription').hide();
        $('.courseName').attr('colspan', '4')
        $('input#truncateDescriptions').prop('disabled', true)
    }
}