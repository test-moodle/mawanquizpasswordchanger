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
 * Plugin administration pages are defined here.
 *
 * @package   local_mawanquizpasswordchanger
 * @copyright 2025 Mawan Agus Nugroho <mawan911@yahoo.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Add the category to the admin tree.
    $settings = new admin_settingpage('local_mawanquizpasswordchanger', get_string('pluginname', 'local_mawanquizpasswordchanger'));
    $ADMIN->add('localplugins', $settings);

    // If salt is empty, default to "Mawan.NET".
    $settings->add(new admin_setting_configtext('local_mawanquizpasswordchanger/salt',
        get_string('salt', 'local_mawanquizpasswordchanger'),
        get_string('salt_desc', 'local_mawanquizpasswordchanger'), 'Mawan.NET'));

    // Validate duration value.
    $duration = get_config('local_mawanquizpasswordchanger', 'duration');
    if ($duration < 1) {
        set_config('duration', 10, 'local_mawanquizpasswordchanger');
    }

    $settings->add(new admin_setting_configtext('local_mawanquizpasswordchanger/duration',
        get_string('duration', 'local_mawanquizpasswordchanger'),
        get_string('duration_desc', 'local_mawanquizpasswordchanger'), 10, PARAM_INT));

    $settings->add(new admin_setting_configtext('local_mawanquizpasswordchanger/serialnumber',
        get_string('serialnumber', 'local_mawanquizpasswordchanger'),
        get_string('serialnumber_desc', 'local_mawanquizpasswordchanger'), ''));

    // Last Token (read-only).
    $currenttoken = get_config('local_mawanquizpasswordchanger', 'token') ?:
        get_string('token_empty', 'local_mawanquizpasswordchanger');
    $settings->add(new admin_setting_heading(
        'local_mawanquizpasswordchanger/token',
        get_string('token', 'local_mawanquizpasswordchanger'),
        $currenttoken
    ));

    // Add heading element to display plain text.
    $currentlastcheck = get_config('local_mawanquizpasswordchanger', 'last_check') ?:
        get_string('last_check_empty', 'local_mawanquizpasswordchanger');
    $settings->add(new admin_setting_heading(
        'local_mawanquizpasswordchanger/last_check',
        get_string('last_check', 'local_mawanquizpasswordchanger'),
        $currentlastcheck
    ));

    // Display serial number expiration information.
    $validuntil = get_config('local_mawanquizpasswordchanger', 'valid_until') ?:
        get_string('valid_until_empty', 'local_mawanquizpasswordchanger');
    $settings->add(new admin_setting_heading(
        'local_mawanquizpasswordchanger/valid_until',
        get_string('valid_until', 'local_mawanquizpasswordchanger'),
        $validuntil
    ));
}
