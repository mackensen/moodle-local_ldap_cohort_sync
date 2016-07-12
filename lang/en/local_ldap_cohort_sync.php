<?php
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

/**
 * @package   local_ldap_cohort_sync
 * @copyright 2016 Lafayette College ITS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 defined('MOODLE_INTERNAL') || die();

$string['group_attribute'] = 'Group attribute';
$string['group_attribute_desc'] = 'Naming attribute of your LDAP groups, usually cn';
$string['group_class'] = 'Group class';
$string['group_class_desc'] = 'Possible values include group, groupOfNames';
$string['pluginname'] = 'Synchronize cohorts from LDAP';
$string['process_nested_groups'] = 'Process nested groups';
$string['process_nested_groups_desc'] = 'If selected, LDAP groups included in groups will be processed';
$string['real_user_attribute'] = 'Real user attribute';
$string['real_user_attribute_desc'] = 'In case your user_attribute is in mixed case in LDAP (sAMAccountName), but not in Moodle\'s CAS/LDAP settings';
$string['synccohortgroup'] = 'Synchronize cohorts with LDAP groups';
