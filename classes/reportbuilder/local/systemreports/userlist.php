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

namespace tool_usergenerator\reportbuilder\local\systemreports;

use core_reportbuilder\system_report;
use core_reportbuilder\local\entities\user;
use core_reportbuilder\local\helpers\database;

/**
 * Class userlist
 *
 * @package    tool_usergenerator
 * @copyright  2024 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class userlist extends system_report {
    /**
     * Validates access to view this report
     *
     * @return bool
     */
    protected function can_view(): bool {
        return has_capability('moodle/user:create', \context_system::instance());
    }

    /**
     * Initialise report. Specify which columns, filters, etc should be present
     */
    protected function initialise(): void {
        $entitymain = new user();
        $entitymainalias = $entitymain->get_table_alias('user');
        $fromid = $this->get_parameter('fromid', 0, PARAM_INT);
        $toid = $this->get_parameter('toid', 0, PARAM_INT);
        if ($fromid) {
            $p = database::generate_param_name();
            $this->add_base_condition_sql("{$entitymainalias}.id >= :{$p}", [$p => $fromid]);
        }
        if ($toid) {
            $p = database::generate_param_name();
            $this->add_base_condition_sql("{$entitymainalias}.id <= :{$p}", [$p => $toid]);
        }

        $this->set_main_table('user', $entitymainalias);
        $this->add_entity($entitymain);

        $columns = [
            'user:fullnamewithpicturelink',
            'user:username',
            'user:email',
        ];

        $this->add_columns_from_entities($columns);
        $this->set_initial_sort_column('user:username', SORT_ASC);
    }
}
