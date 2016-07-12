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
 * @package local_ldap_cohort_sync
 * @copyright 2013 onwards Patrick Pollet
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_ldap', get_string('pluginname', 'local_ldap_cohort_sync'));

    $settings->add(new admin_setting_heading('synccohortgroup',
        new lang_string('synccohortgroup', 'local_ldap_cohort_sync'),
        ''));

    $settings->add(new admin_setting_configtext(
        'local_ldap_cohort_sync/group_attribute',
        new lang_string('group_attribute', 'local_ldap_cohort_sync'),
        new lang_string('group_attribute_desc', 'local_ldap_cohort_sync'),
        'cn'
    ));

    $settings->add(new admin_setting_configtext(
        'local_ldap_cohort_sync/group_class',
        new lang_string('group_class', 'local_ldap_cohort_sync'),
        new lang_string('group_class_desc', 'local_ldap_cohort_sync'),
        'groupOfUniqueNames'
    ));

    $settings->add(new admin_setting_configtext(
        'local_ldap_cohort_sync/real_user_attribute',
        new lang_string('real_user_attribute', 'local_ldap_cohort_sync'),
        new lang_string('real_user_attribute_desc', 'local_ldap_cohort_sync'),
        ''
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_ldap_cohort_sync/process_nested_groups',
        new lang_string('process_nested_groups', 'local_ldap_cohort_sync'),
        new lang_string('process_nested_groups_desc', 'local_ldap_cohort_sync'),
        false
    ));

    $ADMIN->add('localplugins', $settings);
}
