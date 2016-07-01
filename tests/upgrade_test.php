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
 * File containing tests for the 'upgrade' feature.
 *
 * @package     tool_pluginskel
 * @copyright   2016 Alexandru Elisei <alexandru.elisei@gmail.com>, David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use Monolog\Logger;
use Monolog\Handler\NullHandler;
use tool_pluginskel\local\util\manager;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/setuplib.php');
require_once($CFG->dirroot . '/' . $CFG->admin . '/tool/pluginskel/vendor/autoload.php');

/**
 * Upgrade test class.
 *
 * @package     tool_pluginskel
 * @copyright   2016 Alexandru Elisei alexandru.elisei@gmail.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_pluginskel_upgrade_testcase extends advanced_testcase {

    /** @var string[] The test recipe. */
    protected static $recipe = array(
        'component' => 'upgradetest',
        'name'      => 'Upgrade test',
        'copyright' => '2016 Alexandru Elisei <alexandru.elisei@gmail.com>',
        'features'  => array(
            'upgrade' => true
        )
    );

    /**
     * Test creating the db/upgrade.php file.
     */
    public function test_upgrade() {
        $logger = new Logger('upgradetest');
        $logger->pushHandler(new NullHandler);
        $manager = manager::instance($logger);

        $recipe = self::$recipe;
        $manager->load_recipe($recipe);
        $manager->make();

        $files = $manager->get_files_content();
        $this->assertArrayHasKey('db/upgrade.php', $files);
        $this->assertArrayNotHasKey('db/upgradelib.php', $files);
        $upgradefile = $files['db/upgrade.php'];

        $description = 'Plugin upgrade steps are defined here.';
        $this->assertContains($description, $upgradefile);

        $moodleinternal = "defined('MOODLE_INTERNAL') || die()";
        $this->assertContains($moodleinternal, $upgradefile);

        $this->assertNotContains("require_once(__DIR__.'/upgradelib.php')", $upgradefile);
        $this->assertContains('function xmldb_'.$recipe['component'].'_upgrade($oldversion)', $upgradefile);
    }

    /**
     * Test creating the db/upgradelib.php file.
     */
    public function test_upgrade_with_upgradelib() {
        $logger = new Logger('upgradetest');
        $logger->pushHandler(new NullHandler);
        $manager = manager::instance($logger);

        $recipe = self::$recipe;
        $recipe['upgrade'] = array('upgradelib' => true);
        $manager->load_recipe($recipe);
        $manager->make();

        $files = $manager->get_files_content();
        $this->assertArrayHasKey('db/upgradelib.php', $files);
        $upgradefile = $files['db/upgrade.php'];
        $upgradelibfile = $files['db/upgradelib.php'];

        $moodleinternal = "defined('MOODLE_INTERNAL') || die()";
        $this->assertContains($moodleinternal, $upgradelibfile);

        $this->assertContains("require_once(__DIR__.'/upgradelib.php')", $upgradefile);

        $description = 'Plugin upgrade helper functions are defined here.';
        $this->assertContains($description, $upgradelibfile);
        $this->assertContains($recipe['component'].'_helper_function()', $upgradelibfile);
    }
}