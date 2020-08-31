<html>
    <link rel="stylesheet" href="https://my.bpm-music.com/blocks/bpm_utils/search/search.css"/>
    <script src="https://my.bpm-music.com/blocks/bpm_utils/search/search.js"></script>
    <div id='searchOverlay' style='background-color: rgba(0, 0, 0, 0.73); overflow-y:auto; position: fixed; width: 100%; height: 100%; z-index: 100000; top: 0px; right: 0px; cursor: pointer;'>
    <div id='searchContainer'>
        <div id='searchMain'>
            <div id='searchHeader'>
                <h1 style='position:relative;top:10px;'><span id='h1_inner'>חיפוש סטודנטים/קורסים</span><span id='modalCloseBtn' onclick='closeSearchModal();' style='position:absolute;right:6px;color:#dcdcdc;background-color:#000;border-radius:100%;font-size:16px;font-family:Arial;padding:1px 5px 0;top:-6px;border:1px solid #bbb;cursor:pointer'>X</span></h1>
                <input type='search' id='searchBar' placeholder='הקלד טקסט לחיפוש...' onkeyup='ajaxSearch($(this).val())'/>
                <div class='radio-toolbar'>
                    <input type='radio' id='radio2' name='radios' value='students'checked>
                    <label for='radio2'>סטודנטים</label>
                    <input type='radio' id='radio1' name='radios' value='courses'>
                    <label for='radio1'>קורסים</label>
                    
                </div>
            </div>
            <div id='searchResults'>
                <div id='usersAndCourses' class='table-wrapper'>
                    <div id='users'>
                    <h2>משתמשים</h2>
                    <table id='usersTable'><thead><tr class='table-headRow'><th class='hideUs'>מזהה</th>
                        <th>תמונה</th>
                        <th>שם</th>
                        <th>דוא"ל</th>
                        <th>ת.ז.</th>
                        <th>סטטוס</th>
                        <th>חסום דיוור במודל</th>
                        <th>טלפון1</th>
                        <th>טלפון2</th>
                        <th>קורסים</th>
                    </tr></thead><tbody></tbody></table>
                    </div>
                    <div id='courses' class='table-wrapper'>
                        <h2>הרשמות</h2>
                        <table id='coursesTable'>
                        <thead><tr class='table-headRow'>
                            <th>קורס</th>
                            <th>ת.התחלה</th>
                            <th>ת.סיום</th>
                            <th>מרצה</th>
                            <th>ציון</th>
                            <th>נוכחות</th>
                            <th>ציון סופי</th>
                            <th>זמינות</th>
                            <th class='enrollment_rolename'>תפקיד</th>
                        </thead><tbody></tbody></table>
                    </div>
                </div>
                <div id='coursesOnly' class='table-wrapper'>
                <table id='coursesTable2'>
                    <h2>קורסים</h2>
                    <h4>מציג <span class='recordCount'></span> רשומות</h4>
                        <thead><tr class='table-headRow'>
                            <th>קורס</th>
                            <th>ת.התחלה</th>
                            <th>ת.סיום</th>
                            <th>מרצה</th>
                        </thead><tbody></tbody></table>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function() {
    $('#searchBar').focus();
    //getCoursesForUser(136)
    $('.radio-toolbar input').click(function(){
        $('#users, #courses, #coursesOnly').hide();
        $('#searchBar').focus();
        $('#searchBar').trigger('keyup');
    });
    
    $('#searchOverlay, #modalCloseBtn').click(function(e){
     if (e.target !== this) {
    //return false;
     } else {
    	closeSearchModal();
     }
    });
    $(document).keyup(function(e) {
        if (e.keyCode === 27) closeSearchModal();   // esc
    });
});
    
</script>
</html>