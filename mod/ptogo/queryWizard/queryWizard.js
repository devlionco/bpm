function queryWizard(responseTo, submitString, plugin_url) {
    'use strict';
    var container = document.getElementById('queryWizardContainer');
    var target = responseTo;
    var query;
    var counter = 0;
    var response;
    this.getFilters = function() {
        var server = document.getElementById('id_serverurl').value;
        var key = document.getElementById('id_secretkey').value;
        var group = document.getElementById('id_ptogo_group').value;
        var xmlHttp = new XMLHttpRequest();

        xmlHttp.open("POST", plugin_url + "controller.php");
        console.log(plugin_url);
        xmlHttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
        xmlHttp.send("type=filter&server="+server+"&key="+encodeURIComponent(key)+"&group="+group);
        xmlHttp.onload = function() {
            response = JSON.parse(xmlHttp.responseText);
            return this.show();
       }.bind(this);
    };

    this.createList = function(data) {
        if(data.success)
        {
            var successHTML = "";
            var i;
            for(i=0;i< data.response.filters.length;i++)
            {
                successHTML+= '<option value="' + data.response.filters[i].value + '">' + data.response.filters[i].key + '</option>';
            }

            return successHTML;
        } else {
            alert(data.error);
        }
    };

    this.checkVocabularies = function(number) {
        // Read what is selected. Value stores Title, Description, ...
        var value = document.getElementById('subject'+(number-1)).options[document.getElementById('subject'+(number-1)).selectedIndex].value;
        var results = response.response.filters; // Object with all possible filters.
        var i;
        for(i=0;i<results.length;i++) {
            if(results[i].value == value) {
                // In this case filtervalue is a list of values to be appended.
                // TODO: Rewrite this part, it's buggy and ugly.
                if(results[i].useVocabularies === "true") {
                    document.getElementById('item'+(number-1)).removeChild(document.getElementById('value'+(number-1)));
                    var select = document.createElement('select');
                    select.id = 'value'+(number-1);
                    var j;
                    for(j = 0; j < results[i].vocabularies.length; j++) {
                        var option = document.createElement('option');
                        option.value = results[i].vocabularies[j].key;
                        option.appendChild(document.createTextNode(results[i].vocabularies[j].value));
                        select.appendChild(option);
                    }
                    select.addEventListener('change', function() {
                        this.submit();
                    }.bind(this));
                    document.getElementById('item'+(number-1)).insertBefore(select, document.getElementById('search'));
                    return this;
                } else {
                    console.log("Filtervalues are simple text."); //DEBUG
                    // TODO: Check if we have a selectfield.
                    // If it is a select field
                    //remove select value0 and replace with empty text.
                    var obj = document.getElementById('value'+(number-1));
                    console.log(obj);
                    var text=document.createElement('input');
                    text.type='text';
                    text.value='';
                    text.id = 'value'+(number-1);
                    // Re-Add JS listener when changed.
                    text.addEventListener('blur', function() {
                        if(text.value !== "") {
                            this.submit();
                        }
                    }.bind(this));

                    obj.parentNode.insertBefore(text,obj);
                    obj.parentNode.removeChild(obj);
                    // Else simply remove value.
                    

 
                    // Check if we had a vocabulary beforehand and the input is a text field. 
                    // If text field, just delete value.
                    // If not delete the options and add text input.
                    /*
                    document.getElementById('item'+(number-1)).removeChild(document.getElementById('value'+(number-1)));
                    var input = document.createElement('input');
                    input.id = 'value'+(number-1);
                    input.type = 'text';
                    // I imagine this is buggy, we add the input always and exactly prior to search??
                    document.getElementById('item' + (number-1)).insertBefore(input, document.getElementById('search'));
                    */
                    return this;
                }
            }
        }
    }.bind(this);

    // Read all lines of query and update moodle form field.
    this.submit = function() {
        var queryItems = new Array(counter);
        for(var i=0;i<counter;i++) {
            queryItems[i] = document.getElementById('subject' + i).options[document.getElementById('subject' + i).selectedIndex].value;
            queryItems[i] += " " + document.getElementById('filter' + i).options[document.getElementById('filter' + i).selectedIndex].value;
            if (document.getElementById('value' + i).hasAttribute('type')) {
                queryItems[i] += " " + document.getElementById('value'+i).value;
            } else {
                queryItems[i]+= " " + document.getElementById('value' + i).options[document.getElementById('value' + i).selectedIndex].value;
            }
        }
        query = queryItems.join(" AND ");
        // Set the value.
        document.getElementById(target).value = query;

    }.bind(this);


    // Add a new line in the query code to extend query.
    // TODO: Merge with show?
    this.addQuery = function() {
        // Create all the elements.
        var item;
        item = document.createElement('div');
        item.id = 'item'+counter;

        var select = document.createElement('select');
        select.id = 'subject'+counter;
        select.addEventListener('change',function() {
            this.checkVocabularies(counter);
        }.bind(this));
        select.innerHTML = this.createList(response);

        var filter = document.createElement('select');
        filter.id= 'filter'+counter;

        // We also submit when the filter is changed.
        filter.addEventListener('change', function() {
                this.submit();
        }.bind(this));

        var optionlike = document.createElement('option');
        optionlike.value = 'like';
        optionlike.appendChild(document.createTextNode('Like'));

        var optionequals = document.createElement('option');
        optionequals.value = 'equal';
        optionequals.appendChild(document.createTextNode('Equals'));

        var valuebox = document.createElement('input');
        valuebox.type = 'text';
        valuebox.id = 'value' + counter;
        valuebox.addEventListener('blur', function() {
            if(valuebox.value !== "") {
                this.submit();
            }
        }.bind(this));

        var search = new Image();
        search.setAttribute('src', plugin_url + '/pix/iconSearch_32.png');
        search.setAttribute('id', 'search');

        var additionalQuery = new Image();
        additionalQuery.setAttribute('src', plugin_url + '/pix/iconPlus_32.png');
        additionalQuery.setAttribute('id', 'addQuery');
        additionalQuery.addEventListener('click', function(e) {
            e.preventDefault();
            this.addQuery()
        }.bind(this));

        var removex = new Image();
        removex.setAttribute('src', plugin_url + '/pix/iconMinus_32.png');
        removex.setAttribute('id', 'remove'+(counter-1).toString());
        removex.addEventListener('click', function(e) {
            e.preventDefault();
            this.deleteQuery(counter-1);
        }.bind(this));
        //append elements to select list
        filter.appendChild(optionlike);
        filter.appendChild(optionequals);

        //append elements to parent
        item.appendChild(select);
        item.appendChild(filter);
        item.appendChild(valuebox);
        item.appendChild(search);
        item.appendChild(additionalQuery);

        //insert the new element
        container.insertBefore(item, document.getElementById('submit'));

        document.getElementById('item'+ (counter-1).toString()).removeChild(document.getElementById('search'));
        document.getElementById('item'+ (counter-1).toString()).removeChild(document.getElementById('addQuery'));
        document.getElementById('item'+ (counter-1).toString()).appendChild(removex);
        attachNewEvent();

        //increment counter
        counter++;
    }.bind(this);

    this.deleteQuery = function(num) {
        container.removeChild(document.getElementById('item' + (num-1).toString()));
        for(var i=num;i<counter;i++) {
            var children = document.getElementById('item'+ i.toString()).children;
            for(var j=0;j<children.length;j++) {
                children[j].setAttribute('id', children[j].id.replace(i.toString(),(i-1).toString()))
            }
            document.getElementById('item'+ i.toString()).setAttribute('id', document.getElementById('item'+ i.toString()).id.replace(i.toString(), (i-1).toString()));
        }
        counter--;
        // After deleting a line we submit to get the current query.
        this.submit();
    };

    // First setup of query field.
    this.show = function() {
        while(container.hasChildNodes()) {
            container.removeChild(container.firstChild);
        };
        container.style.display = 'block';
        var item = document.createElement('div');
        item.id = 'item' + counter;

        var subject = document.createElement('select');
        subject.id = 'subject' + counter;
        subject.addEventListener('change', function() {
            this.checkVocabularies(counter);
        }.bind(this));
        subject.innerHTML = this.createList(response);

        var filter = document.createElement('select');
        filter.id= 'filter'+counter;
        // We also submit when the filter is changed.
        // TODO: Call also function.
        filter.addEventListener('change', function() {
                this.submit();
        }.bind(this));
                    
                    
        var optionlike = document.createElement('option');
        optionlike.value = 'like';
        optionlike.appendChild(document.createTextNode('Like'));
        var optionequals = document.createElement('option');
        optionequals.value = 'equal';
        optionequals.appendChild(document.createTextNode('Equals'));
        

        var valuebox = document.createElement('input');
        valuebox.type = 'text';
        valuebox.id = 'value' + counter;
        valuebox.addEventListener('blur', function() {
        //valuebox.addEventListener('change', function() {
            if(valuebox.value !== "") {
                this.submit();
            }
        }.bind(this));
        
        var search = new Image();
        search.setAttribute('src', plugin_url + '/pix/iconSearch_32.png');
        search.setAttribute('id', 'search');


        var additionalQuery = new Image();
        additionalQuery.setAttribute('src', plugin_url + '/pix/iconPlus_32.png');
        additionalQuery.setAttribute('id', 'addQuery');
        additionalQuery.addEventListener('click', function(e) {
            e.preventDefault();
            this.addQuery()
        }.bind(this));

        filter.appendChild(optionlike);
        filter.appendChild(optionequals);

        item.appendChild(subject);
        item.appendChild(filter);
        item.appendChild(valuebox);
        item.appendChild(search);
        item.appendChild(additionalQuery);

        container.appendChild(item);
        attachNewEvent();

        counter++;

    }.bind(this);

    this.getFilters();

    return this;
}
