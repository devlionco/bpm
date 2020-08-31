<?php

/**
 * Local plugin "resort courses" - Event definition
 *
 * @package     local
 * @subpackage  local_salesforce
 * @copyright   2013 Alexander Bias, University of Ulm <alexander.bias@uni-ulm.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = array(
    array(
        'eventname'   => '\core\event\course_completed',
        'callback'    => 'local_salesforce_observer::course_criteria_review',
    ),
);
