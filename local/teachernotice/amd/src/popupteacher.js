define(['jquery', 'jqueryui'], function($) {
    
    /* Copied this function from an external source.
       Used to return an object containing the querystring parameters */
    var queryString = function () {
      // This function is anonymous, is executed immediately and 
      // the return value is assigned to QueryString!
      var query_string = {};
      var query = window.location.search.substring(1);
      var vars = query.split("&");
      for (var i=0;i<vars.length;i++) {
        var pair = vars[i].split("=");
            // If first entry with this name
        if (typeof query_string[pair[0]] === "undefined") {
          query_string[pair[0]] = decodeURIComponent(pair[1]);
            // If second entry with this name
        } else if (typeof query_string[pair[0]] === "string") {
          var arr = [ query_string[pair[0]],decodeURIComponent(pair[1]) ];
          query_string[pair[0]] = arr;
            // If third or later entry with this name
        } else {
          query_string[pair[0]].push(decodeURIComponent(pair[1]));
        }
      } 
      return query_string;
    }();

    // Datepicker settings for hebrew customization
    var datepickerHe = function() {
        $('#datepicker').datepicker({
            constrainInput: true,
            draggable: false,
            resizable: false,
            closeText: "סגור",
            prevText: "&#x3C;הקודם",
            nextText: "הבא&#x3E;",
            currentText: "היום",
            monthNames: [ "ינואר","פברואר","מרץ","אפריל","מאי","יוני","יולי","אוגוסט","ספטמבר","אוקטובר","נובמבר","דצמבר" ],
            monthNamesShort: [ "ינו","פבר","מרץ","אפר","מאי","יוני","יולי","אוג","ספט","אוק","נוב","דצמ" ],
            dayNames: [ "ראשון","שני","שלישי","רביעי","חמישי","שישי","שבת" ],
            dayNamesShort: [ "א'","ב'","ג'","ד'","ה'","ו'","שבת" ],
            dayNamesMin: [ "א'","ב'","ג'","ד'","ה'","ו'","שבת" ],
            weekHeader: "Wk",
            dateFormat: "dd/mm/yy",
            firstDay: 0,
            isRTL: true,
            showMonthAfterYear: false,
            yearSuffix: "" 
        });
        $("#datepickerTrigger").click(function() {
            $('#datepicker').datepicker("show");
        });
    };

    var addRecordUrl = "https://my.bpm-music.com/local/teachernotice/add_record.php";
    var dialogElement = $('<div id="dialogElemenet"/>');
    var popupScenarioType = '5';

    var datepickerTypes = {
        "exam" : {
            "title" : "מבחן אמצע",
            "text" : "נא להזין תאריך בו יתבצע מבחן אמצע:",
            "type" : '1'
        },
        "project" : {
            "title" : "פרויקט אמצע",
            "text" : "המערכת זיהתה כי קיים פרויקט אמצע בקורס זה, נא להזין תאריך הגשה:",
            "type" : '3'
        }
    };


    // Reformat a 'dd/mm/yyyy' date string into a 'yyyy-mm-dd' format
    function formatDateISO(date) {
        var dateString = date.substr(6, 4)+"-"+date.substr(3, 2)+"-"+date.substr(0, 2);
        var formattedDate = new Date(dateString);
        formattedDate.setHours(23, 0, 0);

        return formattedDate;
    }

    // Get todays date at 11:00 PM so notices come the morning after
    function getTodayDate() {
        var today = new Date();
        today.setHours(23, 0, 0);

        return today;
    }

    // Generate options for a dialog with datepicker input.
    function generateDialogOptions(dialogTitle, inputRequestText, assignmentType) {
        var dialogInputHtml = '<div>' +
                                  '<p>' + inputRequestText + '</p>' +
                                  '<div>' +
                                      '<input type="text" class="form-control" id="datepicker" disabled="disabled" style="display:inline; width:230px">' +
                                      '<img id="datepickerTrigger" class="icon smallicon" style="margin:0 5px 0 0" src="https://my.bpm-music.com/local/teachernotice/js/theme/calender.svg">' +
                                  '</div>' +
                                  '<p id="noDateInput" style="display:none; margin:0 0 1rem 0">' +
                                      '<i class="fa fa-exclamation-triangle fa-lg" aria-hidden="true" style="color:#ee3e61; margin-left:5px;" />' +
                                      'לא הוזן תאריך' +
                                  '</p>' +
                              '</div>';

        var options = {
            autoOpen:  false,
            draggable: false,
            modal:     true,
            title:     dialogTitle,
            closeOnEscape: false,
            classes: {
                "ui-dialog": "ui-dialog ui-corner-all ui-widget ui-widget-content ui-front ui-dialog-buttons ui-resizable tn-dialog"
            },
            open: function() {
                $(this).html(dialogInputHtml);
                datepickerHe();
            },
            buttons: [{
                text: "אשר",
                click: function() {
                    var assignmentDate = $('#datepicker').val();

                    // If user input exists create notice row for this assignment with the date selected
                    if (assignmentDate !== '') {
                        $(this).dialog('close');
                        var examDate = Math.round(formatDateISO(assignmentDate).getTime() / 1000);             
                        $.post(addRecordUrl, { courseId: queryString.id, assignmentDate: examDate, assignmentType: assignmentType });

                        // If assignment is an exam and its due date is passed, generate a dialog for the mid project (if project exists)
                        if (assignmentType == datepickerTypes.exam.type && popupScenarioType != '1') {
                            var today = Math.round(getTodayDate().getTime() / 1000);
                            if (today >= examDate) {
                                var options = generateDialogOptions(datepickerTypes.project.title, datepickerTypes.project.text, datepickerTypes.project.type);
                                dialogElement.dialog(options).dialog('open');
                                $(".ui-dialog-titlebar-close").hide();
                            }
                        }
                    } else {
                        $('#noDateInput').show();
                    }
                }
            }]
        };

        return options;
    }

    var midExamDialogOptions = {
        autoOpen: false,
        draggable: false,
        modal:    true,
        title:    'מבחן אמצע',
        closeOnEscape: false,
        classes: {
                "ui-dialog": "ui-dialog ui-corner-all ui-widget ui-widget-content ui-front ui-dialog-buttons ui-resizable tn-dialog"
            },

        // Set the main text for the dialog box
        open: function() {
            $(this).html('האם התבצע היום מבחן אמצע?');
        },  
        buttons: [{
            text: "כן",
            click: function() {
                $(this).dialog('close');
                examDate = Math.round(getTodayDate().getTime() / 1000);

                // Add a notice row for this exam with todays date
                $.post(addRecordUrl, { courseId: queryString.id, assignmentDate: examDate, assignmentType: '1' });

                // If exists a project generate a popup dialog for it and show it
                if (popupScenarioType != '1') {
                    var options = generateDialogOptions(datepickerTypes.project.title, datepickerTypes.project.text, datepickerTypes.project.type);
                    dialogElement.dialog(options).dialog('open');
                    $(".ui-dialog-titlebar-close").hide();
                }
            }
        },{ 
            text: "לא",
            click: function() {
                $(this).dialog('close');
                var options = generateDialogOptions(datepickerTypes.exam.title, datepickerTypes.exam.text, datepickerTypes.exam.type);
                $(this).dialog(options).dialog('open');
                $(".ui-dialog-titlebar-close").hide();
            }
        }]
    };

    // Generate popup dialog messages according to the scenario for a specific course
    function generatePopup(popupScenarioTypeParam) {
        popupScenarioType = popupScenarioTypeParam;

        switch(popupScenarioType) {
            case '1':
            case '2':
                dialogElement.dialog(midExamDialogOptions).dialog('open');
                $(".ui-dialog-titlebar-close").hide();
                break;
            case '3':
            case '4':
                var options = generateDialogOptions(datepickerTypes.project.title, datepickerTypes.project.text, datepickerTypes.project.type);
                dialogElement.dialog(options).dialog('open');
                $(".ui-dialog-titlebar-close").hide();
                break;
        }
    }

    return {
        generatePopup : generatePopup
    };
});