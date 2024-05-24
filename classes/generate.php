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

namespace tool_usergenerator;

use stdClass;

/**
 * Class generate
 *
 * @package    tool_usergenerator
 * @copyright  2024 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class generate {
    /** @var array */
    protected $names;
    /** @var array */
    protected $pictures;

    /**
     * Constructor
     */
    public function __construct() {
        global $CFG;
        $basedir = $CFG->dirroot . '/admin/tool/usergenerator';
        $this->names = json_decode(file_get_contents($basedir . '/names.json'), true);

        $allfiles = get_directory_list($basedir.'/pictures', '', true);
        $this->pictures = ['female' => [], 'male' => []];
        foreach ($allfiles as $file) {
            if (preg_match('/^female-.*\.jpg$/', $file)) {
                $this->pictures['female'][] = $basedir . '/pictures/'. $file;
            } else if (preg_match('/^male-.*\.jpg$/', $file)) {
                $this->pictures['male'][] = $basedir . '/pictures/'. $file;
            }
        }
    }

    /**
     * Create users
     *
     * @param array $usernames
     * @param string $password
     * @return array
     */
    public function create_users(array $usernames, string $password): array {
        $users = [];
        foreach ($usernames as $username) {
            $user = $this->create_user($username, $password);
            $users[$user->id] = $user;
        }
        return $users;
    }

    /**
     * Create one user
     *
     * @param string $username
     * @param string $password
     * @return stdClass
     */
    protected function create_user(string $username, string $password): stdClass {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/user/lib.php');
        require_once($CFG->libdir . '/filelib.php');
        require_once($CFG->libdir . '/gdlib.php');

        $male = rand(0, 99) % 2 ? true : false;

        $record = [
            'username' => $username,
            'firstname' => $this->prepare_firstname($male),
            'lastname' => $this->prepare_lastname($male),
            'email' => $this->prepare_email($username),
            'mnethostid' => $CFG->mnet_localhost_id,
            'confirmed' => 1,
            'auth' => 'manual',
        ];

        $userid = user_create_user((object)$record, false, false);

        if ($extrafields = array_intersect_key($record, ['password' => 1, 'timecreated' => 1])) {
            $DB->update_record('user', ['id' => $userid, 'password' => hash_internal_user_password($password)]);
        }

        $user = $DB->get_record('user', ['id' => $userid]);
        $user->imagefile = $draftid = $this->prepare_picture($male);
        \core_user::update_picture($user, []);

        \core\event\user_created::create_from_userid($userid)->trigger();

        return $user;
    }

    /**
     * Generate email that is not used yet
     *
     * @param string $username
     * @return string
     */
    protected function prepare_email(string $username): string {
        global $DB;
        for ($suffix = 0; true; $suffix++) {
            $email = $username . ($suffix ? "-{$suffix}" : '') . '@example.com';
            if (!$DB->record_exists('user', ['email' => $email])) {
                return $email;
            }
        }
    }

    /**
     * Generate random first name
     *
     * @param bool $male
     * @return string
     */
    protected function prepare_firstname(bool $male): string {
        $names = $this->names[$male ? 'male' : 'female'];
        return ucwords(array_rand(array_flip($names)));
    }

    /**
     * Generate random last name
     *
     * @param bool $male
     * @return string
     */
    protected function prepare_lastname(bool $male): string {
        $names = $this->names['lastnames'];
        return ucwords(array_rand(array_flip($names)));
    }

    /**
     * Choose a random profile picture and put it in draft file area
     *
     * @param bool $male
     * @return int draft file area id
     */
    protected function prepare_picture(bool $male): int {
        global $CFG, $USER;
        $pictures = $this->pictures[$male ? 'male' : 'female'];

        $draftitemid = file_get_unused_draft_itemid();
        $record = [
            'filearea' => 'draft',
            'component' => 'user',
            'filepath' => '/',
            'itemid' => $draftitemid,
            'license' => $CFG->sitedefaultlicense,
            'author' => '',
            'filename' => '1.jpg',
            'contextid' => \context_user::instance($USER->id)->id,
            'userid' => $USER->id,
        ];

        $fs = get_file_storage();
        $file = array_rand(array_flip($pictures));
        $fs->create_file_from_pathname($record, $file);

        return $draftitemid;
    }
}
