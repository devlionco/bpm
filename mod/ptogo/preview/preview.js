// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/*
 * Generate a new preview div with videos and checkboxes.
 * parameters: target is field we want to update with videoids.
 *
 * @package   mod_ptogo
 * @copyright 2018 michael pollak <moodle@michaelpollak.org>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function itemPreview(target, submitString, plugin_URL, hasRepository, objectData) {
    'use strict';
    var url = plugin_URL;
    var container = document.getElementById('preview');
    var response;
    if(typeof objectData == "object") {
        var data = JSON.parse(JSON.stringify(objectData));
    } else {
        var data = {};
    }
    var items = new Array();

    this.show = function() {

        while(container.hasChildNodes()) {
            container.removeChild(container.firstChild);
        };
        container.style.display = 'block';
        response = response.response;
        // Walk through every single video found.
        for(var i=0;i<response.length;i++) {

            var item = document.createElement('div');
            item.setAttribute('id', 'preview'+i);
            item.setAttribute('class', 'preview-item');

            var img = new Image();
            img.setAttribute('src', response[i].thumbnailUrl);
            img.setAttribute('class', 'preview-item--image');

            var title = document.createElement('span');
            title.setAttribute('class', 'preview-item--title');
            title.innerHTML = response[i].title;

            item.appendChild(title);
            item.appendChild(img);

            // Checkbox below video creation.
            var checkbox = document.createElement('input');
            checkbox.setAttribute('type', 'checkbox');
            checkbox.setAttribute('name', 'checkbox');
            checkbox.setAttribute('value', response[i].id);
            checkbox.setAttribute('class', 'preview-item--select');
            if(data.items) {
                for (var v = 0; v < data.items.length; v++) {
                    if (data.items[v] == response[i].id) {
                        checkbox.setAttribute('checked', 'checked');
                    }
                }
            }
            // When we change the state of the checkbox, update itemlist.
            checkbox.addEventListener('click', function () {
                var checkboxes = document.querySelectorAll('.preview-item--select');
                var items = []; // Empty out array first.
                for(var i=0; i<checkboxes.length;i++) {
                    if(checkboxes[i].checked) {
                        items.push(checkboxes[i].value);
                    }
                }
                // We write our ; separated list of videos to id_ptogo_item_id formfield.
                document.getElementById(target).value = items.join(";"); // TODO: we get elements by name for hidden.
                // $('input[name='+target+']') = items.join(";"); // TODO: we get elements by name for hidden.

            });

            // Add the checkbox and a short explanation.
            if(hasRepository) {

                var shortexpl = document.createElement('span');
                shortexpl.setAttribute('class', 'preview-item--shortexpl');
                shortexpl.innerHTML = "Add this video to your list."; // TODO: Multilang.

                item.appendChild(shortexpl);
                item.appendChild(checkbox);
            }

            container.appendChild(item);
        }
    };

    this.hide = function() {
        container.style.display = 'none';
    };

    this.getList = function() {
        var xmlHttp = new XMLHttpRequest();

        if(data.query) {
            var addquery = data.query;
        } else {
            var addquery = document.getElementById('id_baseQuery').value;
        }

        if (hasRepository) {
            var repository = document.getElementById('id_repository_id').options[document.getElementById('id_repository_id').selectedIndex].value;

            xmlHttp.open("POST", url + "controller.php");
            xmlHttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xmlHttp.send("type=preview&hasrepository=true&repository=" + repository + "&addQuery=" + encodeURIComponent(addquery));

        } else {
            var server = document.getElementById('id_serverurl').value;
            var key = document.getElementById('id_secretkey').value;
            var group = document.getElementById('id_ptogo_group').value;

            xmlHttp.open("POST", url + "controller.php");
            xmlHttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xmlHttp.send("type=preview&hasrepository=false&server=" + server + "&key=" + key + "&group=" + group + "&addQuery=" + encodeURIComponent(addquery));
        }
        xmlHttp.onload = function () {
            response = JSON.parse(xmlHttp.responseText);
            return this.show();
        }.bind(this);
    };

    this.getList();
}
