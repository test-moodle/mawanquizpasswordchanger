<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_mawanquizpasswordchanger', get_string('pluginname', 'local_mawanquizpasswordchanger'));

    // If salt is empty, default to “Mawan.NET”
    $settings->add(new admin_setting_configtext('local_mawanquizpasswordchanger/salt',
        get_string('salt', 'local_mawanquizpasswordchanger'),
        get_string('salt_desc', 'local_mawanquizpasswordchanger'), 'Mawan.NET'));

    // Validate duration value
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

    // Last Token (read-only)
    $currenttoken = get_config('local_mawanquizpasswordchanger', 'token') ?: get_string('token_empty', 'local_mawanquizpasswordchanger');
    $settings->add(new admin_setting_heading(
        'local_mawanquizpasswordchanger/token',
        get_string('token', 'local_mawanquizpasswordchanger'),
        $currenttoken
    ));

    // Add heading element to display plain text
    $currentlastcheck = get_config('local_mawanquizpasswordchanger', 'last_check') ?: get_string('last_check_empty', 'local_mawanquizpasswordchanger');
    $settings->add(new admin_setting_heading(
        'local_mawanquizpasswordchanger/last_check',
        get_string('last_check', 'local_mawanquizpasswordchanger'),
        $currentlastcheck
    ));

    // Display serial number expiration information.
    $validuntil = get_config('local_mawanquizpasswordchanger', 'valid_until') ?: get_string('valid_until_empty', 'local_mawanquizpasswordchanger');
    $settings->add(new admin_setting_heading(
        'local_mawanquizpasswordchanger/valid_until',
        get_string('valid_until', 'local_mawanquizpasswordchanger'),
        $validuntil
    ));

    $ADMIN->add('localplugins', $settings);
}