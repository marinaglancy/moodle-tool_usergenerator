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

namespace tool_usergenerator\form;

use core_form\dynamic_form;

/**
 * Form to request user generation
 *
 * @package    tool_usergenerator
 * @copyright  2024 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class request extends dynamic_form {
    /**
     * Checks if current user has access to this form, otherwise throws exception
     *
     * Sometimes permission check may depend on the action and/or id of the entity.
     * If necessary, form data is available in $this->_ajaxformdata or
     * by calling $this->optional_param()
     *
     * Example:
     * require_capability('dosomething', $this->get_context_for_dynamic_submission());
     */
    protected function check_access_for_dynamic_submission(): void {
        require_capability('moodle/user:create', \context_system::instance());
    }

    /**
     * Returns context where this form is used
     *
     * This context is validated in {@see \external_api::validate_context()}
     *
     * If context depends on the form data, it is available in $this->_ajaxformdata or
     * by calling $this->optional_param()
     *
     * Example:
     * $cmid = $this->optional_param('cmid', 0, PARAM_INT);
     * return context_module::instance($cmid);
     * @return \context
     */
    protected function get_context_for_dynamic_submission(): \context {
        return \context_system::instance();
    }

    /**
     * Returns url to set in $PAGE->set_url() when form is being rendered or submitted via AJAX
     *
     * This is used in the form elements sensitive to the page url, such as Atto autosave in 'editor'
     *
     * If the form has arguments (such as 'id' of the element being edited), the URL should
     * also have respective argument.
     *
     * Example:
     * $id = $this->optional_param('id', 0, PARAM_INT);
     * return new moodle_url('/my/page/where/form/is/used.php', ['id' => $id]);
     * @return \moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): \moodle_url {
        return new \moodle_url('/admin/tool/usergenerator/index.php');
    }

    /**
     * Process the form submission, used if form was submitted via AJAX
     *
     * This method can return scalar values or arrays that can be json-encoded, they will be passed to the caller JS.
     *
     * Submission data can be accessed as: $this->get_data()
     *
     * Example:
     * $data = $this->get_data();
     * file_postupdate_standard_filemanager($data, ....);
     * api::save_entity($data); // Save into the DB, trigger event, etc.
     */
    public function process_dynamic_submission() {
        $data = $this->get_data();
        $usernames = $this->get_usernames($data->usernameprefix,
            (int)$data->usernameindex, (int)$data->usercount);
        $users = (new \tool_usergenerator\generate())->create_users($usernames, $data->password);
        $userids = array_keys($users);
        return ['fromid' => min($userids), 'toid' => max($userids)];
    }

    /**
     * Load in existing data as form defaults
     *
     * Can be overridden to retrieve existing values from db by entity id and also
     * to preprocess editor and filemanager elements
     *
     * Example:
     * $id = $this->optional_param('id', 0, PARAM_INT);
     * $data = api::get_entity($id); // For example, retrieve a row from the DB.
     * file_prepare_standard_filemanager($data, ...);
     * $this->set_data($data);
     */
    public function set_data_for_dynamic_submission(): void {
    }

    /**
     * Form definition. Abstract method - always override!
     */
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('text', 'usercount', get_string('usercount', 'tool_usergenerator'));
        $mform->setType('usercount', PARAM_TEXT);
        $mform->addRule('usercount', null, 'numeric', null, 'client');
        $mform->setDefault('usercount', 10);

        $mform->addElement('text', 'usernameprefix', get_string('usernameprefix', 'tool_usergenerator'));
        $mform->setType('usernameprefix', PARAM_TEXT);
        $mform->setDefault('usernameprefix', 'user');

        $mform->addElement('text', 'usernameindex', get_string('usernameindex', 'tool_usergenerator'));
        $mform->setType('usernameindex', PARAM_TEXT);
        $mform->addRule('usernameindex', null, 'numeric', null, 'client');
        $mform->setDefault('usernameindex', 1);

        $mform->addElement('text', 'password', get_string('password', 'moodle'));
        $mform->setType('password', PARAM_RAW);
        $mform->setDefault('password', 'test');

        $this->add_action_buttons(false, get_string('generateusers', 'tool_usergenerator'));
    }

    /**
     * Returns list of usernames to be generated
     *
     * @param string $prefix
     * @param int $firstindex
     * @param int $count
     * @return string[]
     */
    protected function get_usernames(string $prefix, int $firstindex, int $count): array {
        $usernames = [];
        for ($i = $firstindex; $i < $firstindex + $count; $i++) {
            $usernames[] = $prefix . $i;
        }
        return $usernames;
    }

    /**
     * Form validation
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    public function validation($data, $files) {
        global $DB, $CFG;
        $errors = parent::validation($data, $files);
        $usernames = $this->get_usernames($data['usernameprefix'],
            (int)$data['usernameindex'], (int)$data['usercount']);

        [$sql, $params] = $DB->get_in_or_equal($usernames, SQL_PARAMS_NAMED);
        $params['mnet'] = $CFG->mnet_localhost_id;

        $existing = $DB->get_fieldset_sql("SELECT username FROM {user} WHERE username $sql AND mnethostid = :mnet", $params);
        if (!empty($existing)) {
            $errors['usernameprefix'] = get_string('error_usernamestaken', 'tool_usergenerator', join(", ", $existing));
        }

        return $errors;
    }
}
