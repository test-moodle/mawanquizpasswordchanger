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

namespace local_mawanquizpasswordchanger\task;

/**
 * Task to send data to mawan.net server.
 *
 * @package    local_mawanquizpasswordchanger
 * @copyright  2025 Mawan Agus Nugroho
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class send_data extends \core\task\scheduled_task {

    /* Variables used:
    salt = String that will be combined with the token generation algorithm.
    duration = Duration in minutes before the quiz password changes.
    serial_number = Serial number that will be sent to mawan.net server.
    valid_until = Date when the serial number will no longer be valid.
    */

    /**
     * Get a descriptive name for this task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('tasksenddata', 'local_mawanquizpasswordchanger');
    }

    function get_token() {
        global $CFG;
        // Get plugin config.
        $config = get_config('local_mawanquizpasswordchanger');

        // If salt is empty, default to "Mawan.NET".
        if (!isset($config->salt) || !is_string($config->salt) || empty($config->salt)) {
            $config->salt = 'Mawan.NET';
            set_config('salt', $config->salt, 'local_mawanquizpasswordchanger');
        };

        // If duration is less than 1, default to 10.
        if (!isset($config->duration) || !is_string($config->duration) || (int) $config->duration < 1) {
            $config->duration = "10";
        }
        set_config('duration', (string)((int) $config->duration), 'local_mawanquizpasswordchanger');

        // Prepare data to send.
        $data = [
            'wwwroot' => $CFG->wwwroot,
            'salt' => $config->salt ?? 'Mawan.NET',
            'duration' => $config->duration ?? '10',
            'serial_number' => $config->serialnumber ?? '',
        ];

        // API endpoint.
        $url = 'https://mmqpc.mawan.net/get/token/';

        try {
            // Initialize curl.
            $ch = curl_init();

            // Set curl options.
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

            // Execute request.
            $response = curl_exec($ch);

            // Check for errors.
            if (curl_errno($ch)) {
                mtrace("Curl error: " . curl_error($ch));
                curl_close($ch);
                return null;
            }

            curl_close($ch);

            // Parse response.
            $result = json_decode($response);

            // Check for errors.
            if (json_last_error() !== JSON_ERROR_NONE) {
                mtrace("An error occurred while decoding JSON: " . json_last_error_msg());
                return null;
            // Check if the response is valid.
            } elseif (isset($result->status) && is_bool($result->status) && isset($result->keterangan) && isset($result->token)) {
                if ($result->status) {
                    // Success - save token.
                    set_config('token', $result->token, 'local_mawanquizpasswordchanger');
                    // Save last successful check time.
                    set_config('last_check', date('Y-m-d H:i:s'), 'local_mawanquizpasswordchanger');
                    // Success - save valid until.
                    $valid_until = isset($result->valid_until) ? $result->valid_until : "";
                    set_config('valid_until', $valid_until, 'local_mawanquizpasswordchanger');
                    if (empty($result->valid_until)) {
                        // Reset to default.
                        set_config('salt', 'Mawan.NET', 'local_mawanquizpasswordchanger');
                        set_config('duration', '10', 'local_mawanquizpasswordchanger');
                    }

                    mtrace("Token updated successfully: " . $result->keterangan);
                    mtrace("New token: " . $result->token);
                    return $result;
                }
                // the response indicates an error.
                else {
                    mtrace("Failed to update token: " . $result->keterangan);
                    return null;
                }
            }
            // Invalid response format.
            else {
                mtrace("Invalid response format");
                return null;
            }
        }
        // An error occurred.
        catch (\Exception $e) {
            mtrace("An error occurred: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Change all active quiz passwords with token.
     */
    public function execute() {
        global $CFG, $DB;

        // Count how many Quizzes are currently open.
        $now = time();

        $sql = "SELECT COUNT(q.id) AS active_quizzes
                FROM {quiz} q
                JOIN {course_modules} cm ON cm.instance = q.id
                JOIN {modules} m ON m.id = cm.module
                WHERE m.name = 'quiz'
                AND cm.visible = 1
                AND (q.timeopen = 0 OR q.timeopen <= :now1)
                AND (q.timeclose = 0 OR q.timeclose > :now2)";

        $params = ['now1' => $now, 'now2' => $now];
        $activequizzes = $DB->get_field_sql($sql, $params);

        $lastcheck = date('Y-m-d H:i:s');
        // No Quizzes are currently open.
        if ($activequizzes < 1) {
            mtrace("No active quiz found. No token request to the Mawan.NET server is required. Last check: $lastcheck.");
            // Save last successful check time.
            set_config('last_check', $lastcheck, 'local_mawanquizpasswordchanger');
            return;
        } else {
            mtrace("$activequizzes active quizzes were found. Last check: $lastcheck.");

            // Search for quizzes that are actively open.
            $sql = "SELECT q.id AS id,
            q.password AS password
            FROM {quiz} q
            JOIN {course_modules} cm ON cm.instance = q.id
            JOIN {modules} m ON m.id = cm.module
            WHERE m.name = 'quiz'
            AND cm.visible = 1
            AND (q.timeopen = 0 OR q.timeopen <= :now1)
            AND (q.timeclose = 0 OR q.timeclose > :now2)";

            $params = ['now1' => $now, 'now2' => $now];
            $activequizzes = $DB->get_records_sql($sql, $params);

            $changed = 0;
            foreach ($activequizzes as $quiz) {
                // Password length must be a 6-digit number.
                if (preg_match('/^\d{6}$/', $quiz->password)) {
                    if (!isset($server)) {
                        mtrace("Sending token request to the Mawan.NET server...");
                        $server = $this->get_token();
                        if (!$server) {
                            mtrace("Token request failed.");
                            return;
                        }
                        mtrace("Successfully obtained token from server.");
                    }
                    $quiz->password = $server->token;

                    // Updating the password in the database.
                    $DB->update_record('quiz', $quiz);
                    $changed++;
                }
            }
            mtrace($changed . " quiz password(s) have been changed.");
        }
    }
}
