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
 * The graph_viewed event.
 *
 * @package    block_meter
 * @copyright  2015 Marty Gilbert <martygilbert@gmail.com> 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_meter\event;
defined('MOODLE_INTERNAL') || die();
/**
 * The graph_viewed event class.
 *
 * @property-read array $other {
 *      Extra information about event.
 *
 *      int courseid - the course id for graph 
 *      String userids - the userids viewed, urlencoded if a list
 * }
 *
 * @since     Moodle 2.7
 * @copyright 2015 Marty Gilbert <martygilbert@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/
class graph_viewed extends \core\event\base {
    protected function init() {
        $this->data['crud'] = 'r'; // c(reate), r(ead), u(pdate), d(elete)
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        //$this->data['objecttable'] = '...'; //??
    }
 
    public static function get_name() {
        return get_string('eventgraph_viewed', 'block_meter');
    }
 
    public function get_description() {
        return "The user with id {$this->userid} viewed a Moodle Meter graph.";
    }
 
    public function get_url() {
        if(isset($this->other['userids'])) {
            $userids = urldecode($this->other['userids']);
            return new \moodle_url('/blocks/meter/user_graph.php', 
                array('id' => $this->other['courseid'], 'userid'=>$userids));
        }

        return null;
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();
        if (!isset($this->other['userids'])) {
            throw new \coding_exception('The \'userids\' value must be set in other.');
        }

        /*
        if ($this->contextlevel != CONTEXT_MODULE) {
            throw new \coding_exception('Context level must be CONTEXT_MODULE.');
        }
        */
    }

}
