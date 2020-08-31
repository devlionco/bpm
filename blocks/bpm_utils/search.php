
<?php
$html = "<div id='SearchOverlay' style='display:none;background-color: rgba(0, 0, 0, 0.73); position: fixed; width: 100%; height: 100%; z-index: 100000; top: 0px; right: 0px; cursor: pointer;'>
    <div id='searchtContainer'>
        <div id='searchMain'>
            <div id='searchHeader'>
                <h1 style='position:relative;top:10px;'><span id='h1_inner'>חיפוש סטודנטים/קורסים</span><span id='modalCloseBtn' style='position:absolute;right:6px;color:#dcdcdc;background-color:#000;border-radius:100%;font-size:16px;font-family:Arial;padding:1px 5px 0;top:-6px;border:1px solid #bbb;cursor:pointer'>X</span></h1>
                <input type='search' id='searchBar' placeholder='הקלד טקסט לחיפוש...' onkeypress='search($(this).val())'/>
                
            </div>
        </div>
    </div>
</div>";

?>
<html>
    <link rel="stylesheet" href="search.css"/>
    <script src="search.js"></script>
    
</html>