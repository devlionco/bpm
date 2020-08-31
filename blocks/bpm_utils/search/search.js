var dalUrl = 'https://my.bpm-music.com/blocks/bpm_utils/search/dal.php'; //not using const because this can get called multiple times and if (typeof X == ' undefined') didn't work as expected so fuckit


function ajaxSearch(input, type) {
    type = $('input[name="radios"]:checked').val();
    
    //console.log(type);
    $('#loadingGif').show();
    console.log(input);
    if (input.length < 2) {
        //console.log('nothing');
        clearSearchTables();
        return false;
    }
    if (type == 'students') {
        mtd = 'searchUser';
        if (input.indexOf(" ") > -1) {
            input = input.split(" ");
            inputType = 'array';
        } else {
            inputType = 'string';
        }
    } else {
        mtd = 'searchCourses';
        inputType = 'string';
    }
    
    let dataObj = {
        input: input,
        mtd: mtd,
        inputType: inputType
    };

    //console.log(dataObj);
    $.post(dalUrl, dataObj, function(jsonData) {
        $('#loadingGif').hide();
        //console.log(jsonData);
        if (type == 'students') {
            let usersHtml = '';
            if (data = JSON.parse(jsonData)) {
                if (Object.keys(data).length > 0) {
                    Object.keys(data).forEach(function(item, index) {
                        console.log(item);
                        
                       //console.log(data[item]); //id, firstname, lastname, email, username, suspended, emailstop, phone1, phone2, picture  
                       let itemRow = "<tr class='search_user-row'>";
                       let nameFlag = false;
                       Object.keys(data[item]).forEach(function(user_item, inner_index) {
                           switch (user_item) {
                                case "firstname":
                                case "lastname":
                                    if (!nameFlag) {
                                        itemRow += "<td class='user_name'><a class='' target='_blank' href='https://my.bpm-music.com/user/view.php?id=" + data[item].id + "'>" + data[item].firstname +  " " + data[item].lastname + "</a></td>";
                                        nameFlag = true;
                                    }
                                    break;
                                case "picture":
                                    itemRow += "<td class='user_picture'>" + "<a class='userRowUrl' href='#" + data[item].id + "'><img src='https://my.bpm-music.com/user/pix.php/" + data[item].id + "/f1.jpg'/>" + "</a></td>";    
                                    break;
                                case "coursecount":
                                    itemRow += "<td class='course_count'><a class='userRowUrl' href='#" + data[item].id + "'><i class='fa fa-list-alt aria-hidden='true'></i>" + data[item].coursecount + "</a></td>";
                                    break;
                                default:
                                    itemRow += "<td class='" + user_item + "'>" + data[item][user_item] + "</td>";    
                           }
                       });
                       itemRow += "</tr>";
                       usersHtml += itemRow;
                    });
                } else {
                    usersHtml = "לא נמצאו תוצאות עבור מילת החיפוש שהוזנה.";
                }
                
                $('#searchResults, #users').show();
                $('#usersTable tbody').html(usersHtml);
                anchorListeners();
            } else {
                console.log('problema');
            }
        } else {
            let courseResultsHtml = '';
            if (data = JSON.parse(jsonData)) {
                $('#coursesOnly .recordCount').text(Object.keys(data).length);
                Object.keys(data).forEach(function(item, index) {
                    
                   //console.log(data[item]); //id, firstname, lastname, email, username, suspended, emailstop, phone1, phone2, picture  
                   let itemRow = "<tr class='search_course-row'>";
                   let courseNameFlag = false;
                   let instructorNameFlag = false;
                   Object.keys(data[item]).forEach(function(course_item, inner_index) {
                       switch (course_item) {
                           case "id":
                           case "coursename":
                                if (!courseNameFlag) {
                                     let statusClassName = getCourseStatusClassName(data[item].startdate, data[item].enddate);
                        	        itemRow += "<td class='courseName'>\
                                     <a class='courseGradesUrl " + statusClassName + "' href='https://my.bpm-music.com/grade/report/grader/index.php?id=" + 
                                                data[item].id + "' target='_blank' title='למעבר ללוח ציונים'>" + 
                                                "<i class='fa fa-th-list' aria-hidden='true'></i></a>\
                                                <a class='coursePageUrl' target='_blank' title='למעבר לעמוד הקורס' href='https://my.bpm-music.com/course/view.php?id=" + 
                                                data[item].id + "'>" + 
                                                data[item].coursename + "</a></td>";
                                    courseNameFlag = true;
                                }
                                break;
                            case "startdate":
                            case "enddate":
                                itemRow += "<td class='courseDate'>" + unixToHuman(data[item][course_item]) + "</td>";    
                                break;
                                
                            case "instructorid":
                            case "instructorname":
                                if (!instructorNameFlag) {
                                    itemRow += "<td class='instructorName'>\
                                                <a class='instructorPageUrl' title='למעבר לעמוד המרצה' href='https://my.bpm-music.com/user/view.php?id=" + 
                                                data[item].instructorid + "'>" + 
                                                data[item].instructorname + "</a></td>";
                                    instructorNameFlag = true;
                                }
                                break;
                            default:
                                itemRow += "<td class='" + course_item + "'>" + data[item][course_item] + "</td>";
                       }
                   });
                   itemRow += "</tr>";
                   courseResultsHtml += itemRow;
                });
                $('#searchResults, #coursesOnly').show();
                $('#coursesOnly tbody').html(courseResultsHtml);
                anchorListeners();
            } else {
                console.log('problema');
            }
        }
    });
}

function clearSearchTables() {
    $('tbody').html('');
    $('#users, #courses, #coursesOnly, #searchResults').hide();
}

function anchorListeners() {
    $('a.userRowUrl').each(function(){
        $(this).click(function(){
            $('.highlightedRow').removeClass('highlightedRow');
            $(this).parents('tr').addClass('highlightedRow');
            $(this).parents('tr').siblings('tr.search_user-row').remove();
            let thisUserId = $(this).attr('href').replace('#', ''),
                thisUserName = $(this).parents('tr').find('td.user_name a').text();
                getCoursesForUser(thisUserId, thisUserName);
        });
    });
}

function getCoursesForUser(userid, userName) {
    console.log('userid:', userid, 'userName: ', userName);
    $('#courses h2').text('הרשמות של ' + userName)
    let coursesHtml = '';
    //console.log('getting courses for user: ', userid);
    $.post(dalUrl, {mtd: 'getCoursesForUser', userid: userid}, function(rawData) {
       // console.log(rawData); 
        
        if (data = JSON.parse(rawData)) { //c.id, c.shortname as coursename, c.startdate, c.enddate, CONCAT(u2.firstname, ' ', u2.lastname) as instructorname, u2.id as instructorid, sfe.grade,  sfe.attendance, sfe.account_sfid, sfe.completegrade, ue.status, r.shortname as rolename
            //console.log(data);  
            if (Object.keys(data).length > 0) {
                Object.keys(data).forEach(function(item, index) {
                    let rowKey = item, rowVal = data[item];
                    // console.log('item: ', item);
                    // console.log('data[item]: ', data[item]); 
                    let itemRow = "<tr class='search_course-row'>";
                    let courseNameFlag = false;
                    let skippies = ['id', 'account_sfid', 'instructorid']
                    if (Object.keys(data[item]).length > 0) {
                        Object.keys(data[item]).forEach(function(col, inner_index) {
                            if (skippies.includes(col)) {
                                return false;
                            }
                            switch (col) {
                            case "id":
                            case "coursename":
                                if (!courseNameFlag) {
                                    itemRow += "<td class='courseName'>\
                                     <a class='courseGradesUrl' href='https://my.bpm-music.com/grade/report/grader/index.php?id=" + 
                                                data[item].id + "' target='_blank' title='למעבר ללוח ציונים'>" + 
                                                "<i class='fa fa-th-list' aria-hidden='true'></i></a>\
                                                <a class='coursePageUrl' target='_blank' title='למעבר לעמוד הקורס' href='https://my.bpm-music.com/course/view.php?id=" + 
                                                data[item].id + "'>" + 
                                                data[item].coursename + "</a></td>";
                                    courseNameFlag = true;
                                }
                                break;
                            case "attendance":
                            case "grade":
                                if (parseFloat(data[item][col]).toFixed(1) < 0) {
                                    intData = '-';
                                } else {
                                    intData = parseFloat(data[item][col]).toFixed(1);
                                }
                                itemRow += "<td class='enrollment_data'>" + intData + "</td>";
                                break;
                            case "rolename":
                                let roleClassName = '';
                                if (data[item].rolename != "student") {
                                    roleClassName = data[item].role;
                                }
                                itemRow +="<td class='enrollment_rolename " + roleClassName + "'>" + translateRoleName(data[item][col]) + "</td>";
                                break;
                            case "completegrade":
                                itemRow += "<td class='enrollment_" + col + "'>" + styledBool(data[item][col], false) + "</td>";
                                break;
                            case "status":
                                itemRow += "<td class='enrollment_" + col + "'>" + styledBool(data[item][col], true) + "</td>";
                                break;
                            case "startdate":
                            case "enddate":
                                itemRow += "<td class='courseDate'>" + unixToHuman(data[item][col]) + "</td>";    
                                break;
                            default:
                                itemRow += "<td class='" + col + "'>" + data[item][col] + "</td>";    
                       }
                    });
                   itemRow += "</tr>";
                   coursesHtml += itemRow;
                } else {
                    coursesHtml += "לא נמצאו תוצאות עבור מילת החיפוש שהוזנה.";
                }
            });
            
            $('#courses').show();
            $('#coursesTable tbody').html(coursesHtml);
            
            //anchorListeners(); //TODO add listeners for future enrollment-editing buttons on each row
                
            } else {
                console.log('problem getting courses');
            }
        }
    });
}

function getCourseStatusClassName(startdate, enddate) {
    let now  = Math.round((new Date()).getTime() / 1000);
        if (enddate == 0) {
            output = 'courseParent';
        }
        else if (now < startdate) {
            output = 'futureCourse';
        } else if (now > enddate) {
            output = 'pastCourse';
        } else {
            output = 'activeCourse';
        }
        
        return output;
}

function translateRoleName(input) {
    switch (input) {
        case 'editingteacher':
            output = 'מורה';
            break;
        case 'teacher':
            output = 'מחליף';
            break;
        default:
            output = input;
    }
    return output;
}

function unixToHuman(input) {
    if (input != 0 && input) {
        let output = new Date(input * 1000);
        let finalString =  output.getDate() + "/" + (output.getMonth() + 1) + "/" + output.getFullYear().toString().substr(2);
        if (finalString == '1/1/16') {
            return '-';
        } else {
            return finalString;
        }
    } else {
        return '-';
    }
    
}

function styledBool(input, reversePolarity) {
    if (reversePolarity) {
        if (input == '1') {
            output = "";
        } else {
            output = "✓";
        }
    } else {
        if (input == '1') {
            output = "✓";
        } else {
            output = "";
        }
    }
    return output;
}

function closeSearchModal() {
    $('#searchOverlay').remove();
}
