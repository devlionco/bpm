This is the Mass Actions block for Moodle 3.2.

Created at University of Minnesota by the Custom Solutions team.

To install using git, type this command in the root of your Moodle install
    git clone git@github.com:at-tools/moodle-block_massaction.git

Alternatively, download the zip from
    https://github.com/at-tools/moodle-block_massaction/archive/moodle-32-master.zip
Unzip it into the blocks folder, and then rename the new folder to massaction.

Once installed, capability "block/massaction:use" needs to be added to the roles/users
(e.g. teacher) in order for them to be able to use the block.


RELEASE NOTE
[2017111700]
- Fixed error in javascript so the error message will actually display when
    no checkboxes are checked

[2017092900]
- Refactored the javascript to remove all but two lint warnings
- Updated documentation in the php files to remove code pre-check warnings

[2017092801]
- Correct function call when drawing checkboxes from append() to appendChild(),
    which the latest versions of Chrome and Firefox are fault-tolerant of, whereas
    older versions of Firefox (possibly Chrome, too; unknown) were not and would
    throw a javascript error when encountered

[2017092800]
- Remove the javascript-enabled check and configuration page completely

[2017092600]
- Re-implement the deletion confirmation page that displays after the user responds
    in the affirmative to the javascript confirmation dialog box
- Moved the table heading text into language strings
- Added a configuration page and setting for controlling whether to have the block check
    whether javascript is enabled in the user's browser
- All new French language strings courtesy Google Translate
    - Better translations are welcome
- Hebrew translations have not been done; Any translations are welcome

[2017092000]
- Fixed typo in javascript causing checkboxes not to draw (missing hyphen)

[2017091800]
*** Behavioural changes ***
- Removed the deletion confirmation page. When deleting one or more modules using
    Mass Actions, there is now only a Javascript confirmation. The confirmation page
    felt redundant and so I removed it.
- When making a selection from the "Select all in section" menu, the "Deselect all"
    action is invoked first. In the event of a misclick, this eliminates the need to
    click "Deselect all" or to manually uncheck any checked modules before making
    the correct selection from the menu. However, it means that users can no longer
    select all in two or more sections using the menu, which was probably a fairly
    rare case.
- Users of the OneTopic format may now use "Select all" to apply an action to all
    modules in their course.
- Users of the OneTopic format may now use "Select all in section" to select any
    section in their course that has modules. Previously, they were able to only
    select the section displayed on their screen. This makes the OneTopic course
    format behave like all other course formats.

*** Technical changes ***
- Converted from YUI to jQuery/AMD library
- Added ./amd/src/block_massaction.js
- Removed ./module.js and ./js/module_selector.js
- applicable_formats() now uses course_format_uses_sections() to determine whether
    to make Mass Actions available to a course format
- get_content() now provides a two dimensional array of course data to the Javascript,
    reducing the block's dependence on the DOM (and reducing its fragility)
- Added several hidden inputs to the form submitted when the user chooses the action
    to take on selected modules
- Added the _self_test function
- Changed 'moveleft' to 'outdent', 'moveright' to 'indent', 'moveto' to 'move', and
    'dupto' to 'clone'
- Renamed the 'perform_moveto' function as 'move_module' and the 'perform_dupto' function
    as 'clone_module'
- Removed the print_deletion_confirmation function
- Removed code from the perform_deletion function that has long been obsolete, as it was
    specific to Moodle 2.4 and earlier
- Moved code duplicated in move_module(), clone_module(), and perform_deletion() to new functions
    and replaced that code with function calls
- Moved capability checks that previously happened prior to calling the functions to change
    indentation or visibility to happen inside the functions, instead. This makes the timing
    and location of the capability checks for these actions consistent with the timing and
    location of the capability checks for the other actions.
- Cleaned up the naming scheme for CSS class names and ids, with the overall goal being
    to simplify them and make them more logical and predictable
- Updated styles.css as necessary
- Updated some language string keys to match the changes in naming conventions; this work
    will probably be on-going
- Removed some language strings that were clearly obsolete and no longer used

[2017062800]
- Fix Boost theme bug that caused checkboxes not to display (lbroda)

[2017013100]
- Fix Travis-CI jshint errors when running builds

[2017013000]
- Removed one of the non-Javascript deletion confirmation steps
- Corrected call to redirect() when attempting to delete activities to prevent errors being
  thrown on deletion confirmation page

[2016111700]
- Updated the documentation for this.sections in js/module_selector.js
- Overlooked adding '.modules' to one line of code checking whether any
  modules have been added for a section

[2016111600]
- Improved the code that supports the OneTopic format, making it less reliant on
  properties the user can change

[2016111502]
- Change required Moodle version to 3.2 (2016102700)
- Bump plugin version to 4.0.0 to signify this is not compatible with earlier
  versions

[2016111501]
- Fix bug with OneTopic format compatibility where, if the topics were not named
  'Topic X' (where X is an integer greater than -1), the block would be unusable
  because it would not draw checkboxes next to modules and would not correctly
  populate all of its drop menus

[2016111500]
- Remove h3 tag requirement from module.js so Mass Actions will work with themes
  that use non-h3 tags for section headings, from Dan Davis (rndme)

[2016101301]
- Enable compatibility with OneTopic course format
- Bump version to 2.0.0 to signal this has diverged from the version of this
  plugin that is compatible with Moodle 2.7 and earlier
- Change three variables in action.php::print_deletion_confirmation to be
  arguments instead of global variables

[2016101300]
- Integrate with Travis CI and fix errors and warnings

[2016101000]
- Fixed a regression created when I added the Javascript-disabled functionality
  that caused checkboxes not to be drawn in Flexible Sections formatted courses

[2016052401]
- Moved the string displayed when Javascript is disabled into the language file.

[2016052400]
- Enabled the block to inform the user, when Javascript is disabled, that Javascript
  is required in order to use the block

[2016052300]
- Fix bug with Topics/Weekly formats when Course Layout is set to 'Show one section
  per page' from Matt Davidson (syxton)

[2016030400]
- Fix bug with Flexible Sections course format where multiple checkboxes would be
  displayed for each activity in a sub-section

[2016022400]
- Add 'Duplicate to' functionality from Matt Davidson (syxton)

[2015120100]
- Merge a deletion confirmation prompt from Rex Lorenzo (rlorenzo)

[2015091400]
- Improved checkbox processing to make it more robust, in case there are non-input
  elements with an id matching the expected pattern

[2015032600]
- Updated applicable_formats() to allow any course format, while still
  preventing plugins and tags from using this block (sharpchi)

[2015022700]
- Renamed README to README.txt
- Added $plugin->component for Moodle 3.0 compatibility
- Changed $plugin->release to an actual version number
- Updated course formats for which this plugin is available to include all of:
    Flexible Sections, Collapsed Topics, Topics, and Weekly.
    **If you use a course format not listed and feel it should be able to use
    the Mass Actions block, please let me know and I will install your course
    format plugin and test this block with that format.

[2014081900]
- merge lang string from Skylar Kelty (sk-unikent)
- Cosmetic change to move drop-down to new line

[2013112101]
- merge Hebrew translation from Nadav Kavalerchik (nadavkav)

[2013112100]
- initialize $this->content properly to avoid strict warning

[2013112000]
- convert calls to deprecated get_context_instance() to context_xxx::instance()
- fix javascript to work with 2.6, catch errors to avoid breaking the page' script
- use course_delete_module() when available (Moodle 2.5 and above)

[2013040400]
- try to parse the section names into the listboxes when possible

[2013040100]
- updated to be compatible with Moodle 2.4

[2012032201]
- added additional checking to avoid Javascript error

[2012032200]
- added French translation from Luiggi Sansonetti

[2012012500]
- fixed incorrect call to rebuild_course_cache(), which rebuild all courses leading to
performance problem.

[2011081500]
- initial release
