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
 * Main page of the generator
 *
 * @package    tool_usergenerator
 * @copyright  2024 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../config.php');

require_once(__DIR__ . '/../../../config.php');
require_once("{$CFG->libdir}/adminlib.php");

admin_externalpage_setup('tool_usergenerator');

$fromid = optional_param('fromid', 0, PARAM_INT);
$toid = optional_param('toid', 0, PARAM_INT);

$form = new tool_usergenerator\form\request();
if ($data = $form->get_data()) {
    $params = $form->process_dynamic_submission();
    redirect(new moodle_url($PAGE->url, $params));
}

$PAGE->set_heading($SITE->fullname);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'tool_usergenerator'));
echo "<div>" . get_string('plugindescription', 'tool_usergenerator') . "</div>";
if ($fromid && $toid) {
    // Show users generated in the previous step.
    $report = \core_reportbuilder\system_report_factory::create(
        tool_usergenerator\reportbuilder\local\systemreports\userlist::class,
        context_system::instance(), '', '', 0, ['fromid' => $fromid, 'toid' => $toid]);
    echo $report->output();
}
$form->display();
echo $OUTPUT->footer();
