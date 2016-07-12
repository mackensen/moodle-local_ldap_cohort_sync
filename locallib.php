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
 * @copyright 2013 Patrick Pollet
 * @copyright 2016 Lafayette College ITS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/auth/ldap/auth.php');
require_once($CFG->dirroot . '/cohort/lib.php');

class local_ldap_cohort_sync extends auth_plugin_ldap {
    private $groupdncache = array();

    public function __construct() {
        if (is_enabled_auth('cas')) {
            $this->authtype = 'cas';
            $this->roleauth = 'auth_cas';
        } else if (is_enabled_auth('ldap')) {
            $this->authtype = 'ldap';
            $this->roleauth = 'auth_ldap';
        } else {
            return false;
        }
        $this->init_plugin($this->authtype);

        // Load plugin configuration.
        $config = get_config('local_ldap_cohort_sync');

        // Merge settings with core LDAP plugin.
        if (!empty($config->real_user_attribute)) {
            $this->config->user_attribute = $config->real_user_attribute;
        }
        $this->merge_plugin_config($config, 'group_attribute', 'cn');
        $this->merge_plugin_config($config, 'group_class', 'groupOfNames');
        $this->merge_plugin_config($config, 'process_nested_groups', false);
    }

    private function merge_plugin_config($local, $key, $default) {
        if (!empty($local->key)) {
            $this->config->$key = $local->$key;
        } else {
            $this->config->$key = $default;
        }
    }

    /**
     * Return all groups from the LDAP instance.
     * @return array
     */
    public function get_groups($filter = '') {
        $ldapconnection = $this->ldap_connect();
        $foundgroups = array();

        if (empty($filter)) {
            $filter = "(&(" . $this->config->group_attribute . "=*)(objectclass=" . $this->config->group_class . "))";
        }

        $contexts = explode(';', $this->config->contexts);
        foreach ($contexts as $context) {
            $context = trim($context);
            if (empty($context)) {
                continue;
            }

            if ($this->config->search_sub) {
                // Use ldap_search to find first group from subtree.
                $ldapresult = ldap_search($ldapconnection, $context, $filter, array($this->config->group_attribute));
            } else {
                // Search only in this context.
                $ldapresult = ldap_list($ldapconnection, $context, $filter, array($this->config->group_attribute));
            }
            $groups = ldap_get_entries($ldapconnection, $ldapresult);

            // Add groups to list.
            if ($groups['count'] > 0) {
                for ($i = 0; $i < count($groups) - 1; $i++) {
                    $cn = $groups[$i][$this->config->group_attribute][0];
                    $foundgroups[] = $cn;

                    if ($this->config->process_nested_groups) {
                        $this->groupdncache[$groups[$i]['dn']] = $cn;
                    }
                }
            }
        }
        $this->ldap_close();
        return $foundgroups;
    }

    public function get_group_members($group) {
        if ($this->config->user_type == 'ad') {
            // TODO: Implement AD support.
        } else {
            $members = $this->get_group_members_openldap($group);
        }

        return $members;
    }

    private function get_group_members_openldap($group) {
        $ldapconnection = $this->ldap_connect();
        $group = trim(core_text::convert($group, 'utf-8', $this->config->ldapencoding));
        $query = "(&({$this->config->group_attribute}=$group)(objectClass={$this->config->group_class}))";
        $users = array();

        $contexts = explode(';', $this->config->contexts);
        foreach ($contexts as $context) {
            $context = trim($context);
            if (empty($context)) {
                continue;
            }
        }
        $result = ldap_search($ldapconnection, $context, $query);
        if (!empty($result) && ldap_count_entries($ldapconnection, $result)) {
            $groupresult = ldap_get_entries($ldapconnection, $result);
            for ($i = 0; $i < count($groupresult[0][$this->config->memberattribute]) - 1; $i++) {
                $memberstring = trim($groupresult[0][$this->config->memberattribute][$i]);
                if (!empty($memberstring)) {
                    $member = explode(',', $memberstring);
                    if (count($member) > 1) {
                        // Extract the CN from the username.
                        $memberparts = explode('=', trim($member[0]));

                        // Verify that this is a user.
                        $found = core_text::strtolower($memberparts[0]) === core_text::strtolower($this->config->user_attribute);
                        if ($found) {
                            $users[] = core_text::strtolower($memberparts[1]);
                        } else {
                            // TODO: support for nested groups.
                        }
                    } else {
                        $users[] = core_text::strtolower($member);
                    }
                }
            }
        }
        $this->ldap_close();
        return $users;
    }

    public function get_cohort_members($cohortid) {
        global $DB;
        $sql = " SELECT u.id,u.username
                  FROM {user} u
                 JOIN {cohort_members} cm ON (cm.userid = u.id AND cm.cohortid = :cohortid)
                WHERE u.deleted=0";
        $params['cohortid'] = $cohortid;
        return $DB->get_records_sql($sql, $params);
    }

    public function sync_cohorts() {
        global $DB;
        $groups = $this->get_groups();

        foreach ($groups as $group) {
            // Verify that the cohort exists.
            if (!$cohort = $DB->get_record('cohort', array('idnumber' => $group), '*')) {
                continue;
            }
            $ldapmembers = $this->get_group_members($group);
            $cohortmembers = $this->get_cohort_members($cohort->id);
            foreach ($cohortmembers as $userid => $user) {
                if (!isset ($ldapmembers[$userid])) {
                    cohort_remove_member($cohort->id, $userid);
                }
            }
            foreach ($ldapmembers as $userid => $user) {
                if (!cohort_is_member($cohort->id, $userid)) {
                    cohort_add_member($cohort->id, $userid);
                }
            }
        }
    }
}
