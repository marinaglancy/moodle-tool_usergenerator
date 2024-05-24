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

/**
 * Tests for User generator
 *
 * @covers     \tool_usergenerator\generate
 * @package    tool_usergenerator
 * @category   test
 * @copyright  2024 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class generate_test extends \advanced_testcase {

    public function test_create_users(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $users = (new generate())->create_users(['user1', 'user2'], 'test');
        $this->assertCount(2, $users);
        $users = array_values($users);
        $this->assertEquals('user1', $users[0]->username);
        $this->assertEquals('user2', $users[1]->username);
    }
}
