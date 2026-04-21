<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * All in one init script - PHP version.
 *
 * @package    tool_phpunit
 * @copyright  2012 Petr Skoda {@link http://skodak.org}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_h5p\core;

if (isset($_SERVER['REMOTE_ADDR'])) {
    die; // No access from web!
}

// Force OPcache reset if used, we do not want any stale caches
// when preparing test environment.
if (function_exists('opcache_reset')) {
    opcache_reset();
}

define('IGNORE_COMPONENT_CACHE', true);

// It makes no sense to use BEHAT_CLI for this script (you cannot initialise PHPunit starting from
// the Behat environment), so in case user has set tne environment variable, disable it.
putenv('BEHAT_CLI=0');

require_once(__DIR__.'/../../../../lib/clilib.php');
require_once(__DIR__.'/../../../../lib/phpunit/bootstraplib.php');
require_once(__DIR__.'/../../../../lib/testing/lib.php');

$autoload = __DIR__ . '/../../../../../vendor/autoload.php';
if (file_exists($autoload)) {
    require_once($autoload);
}

list($options, $unrecognized) = cli_get_params(
    [
        'help'                 => false,
        'disable-composer'     => false,
        'composer-upgrade'     => true,
        'composer-self-update' => true,
    ],
    [
        'h' => 'help',
    ]
);

$help = "
Utilities to initialise the PHPUnit test site.

Usage:
  php init.php [--no-composer-self-update] [--no-composer-upgrade]
               [--help]

--no-composer-self-update
                    Prevent upgrade of the composer utility using its self-update command

--no-composer-upgrade
                    Prevent update development dependencies using composer

--disable-composer
                    A shortcut to disable composer self-update and dependency update
                    Note: Installation of composer and/or dependencies will still happen as required

-h, --help          Print out this help

Example from Moodle root directory:
\$ php admin/tool/phpunit/cli/init.php
";

if (!empty($options['help'])) {
    echo $help;
    exit(0);
}

echo "Initialising Moodle PHPUnit test environment...\n";

if ($options['disable-composer']) {
    // Disable self-update and upgrade easily.
    // Note: Installation will still occur regardless of this setting.
    $options['composer-self-update'] = false;
    $options['composer-upgrade'] = false;
}

// Install and update composer and dependencies as required.
testing_update_composer_dependencies($options['composer-self-update'], $options['composer-upgrade']);

$output = null;
exec('php --version', $output, $code);
if ($code != 0) {
    phpunit_bootstrap_error(1, 'Can not execute \'php\' binary.');
}

chdir(__DIR__);
$output = null;
exec("php util.php --diag", $output, $code);

$moodleroot = dirname(__DIR__, 5);
// Read version from version.php.
preg_match('/\$version\s*=\s*(\d+)/', file_get_contents($moodleroot . '/public/version.php'), $matches);
$version = $matches[1];
$snapshotdir = "/var/lib/sitedata/phpunit_snapshots/$version";

if ($code == PHPUNIT_EXITCODE_INSTALL) {
    // This is the first time install, so we go through full installation process then save a snapshot.
    passthru("php util.php --install", $code);
    if ($code != 0) {
        exit($code);
    }
    // Save the initial db snapshot.
    save_snapshot();

} else if ($code == PHPUNIT_EXITCODE_REINSTALL) {
    if (is_dir($snapshotdir)) {
        // If the snapshot exists, restore the db based on the snapshot and then run upgrade process.
        restore_and_upgrade();
    } else {
        // If there is no snapshot, run the full phpunit install.
        echo "No snapshot found for Moodle $version — running full reinstall...\n";
        passthru("php util.php --drop", $code);
        passthru("php util.php --install", $code);
        if ($code != 0) {
            exit($code);
        }
        // Always override existing snapshot at the end.
        save_snapshot();
    }

} else if ($code != 0) {
    echo implode("\n", $output)."\n";
    exit($code);
}

passthru("php util.php --buildconfig", $code);

echo "\n";
echo "PHPUnit test environment setup complete.\n";
exit(0);

// Function that saves a snapshot of the current state of the PHPUnit DB.
function save_snapshot() {
    global $snapshotdir, $moodleroot;
    echo "Saving PHPUnit snapshot to $snapshotdir...\n";

    if (!is_dir($snapshotdir)) {
        mkdir($snapshotdir, 0777, true);
    }

    // Bootstrap Moodle.
    define('CACHE_DISABLE_ALL', true);
    define('PHPUNIT_UTIL', true);
    require_once($moodleroot . '/vendor/autoload.php');
    require_once($moodleroot . '/public/lib/phpunit/bootstrap.php');
    initialise_cfg();

    global $CFG;

    exec("cp -r {$CFG->phpunit_dataroot} $snapshotdir/phpunitdata", $out, $exitcode);
    if ($exitcode !== 0) {
        echo "Error: failed to copy phpunitdata.\n";
        exit(1);
    }

    echo "Snapshot saved.\n";
}


// Function that restores the PHPUnit DB from the stored snapshot, then upgrades all plugins to ensure any new/missing ones are installed.
function restore_and_upgrade() {
    global $snapshotdir, $moodleroot;
    echo "Restoring PHPUnit snapshot from $snapshotdir...\n";

    if (!defined('CACHE_DISABLE_ALL')) {
        define('CACHE_DISABLE_ALL', true);
    }
    if (!defined('PHPUNIT_UTIL')) {
        define('PHPUNIT_UTIL', true);
    }
    require_once($moodleroot . '/vendor/autoload.php');
    require_once($moodleroot . '/public/lib/phpunit/bootstrap.php');
    initialise_cfg();

    global $CFG;

    // Restore phpunitdata.
    exec("rm -rf {$CFG->phpunit_dataroot}");
    exec("cp -r $snapshotdir/phpunitdata {$CFG->phpunit_dataroot}");

    \core\test\phpunit\phpunit_util::reset_database();

    echo "Snapshot restored.\n\n";

    require_once($CFG->libdir . '/adminlib.php');
    require_once($CFG->libdir . '/upgradelib.php');

    $start = function ($component, $installing, $verbose) {
        echo ($installing ? 'Installing' : 'Upgrading') . " $component...\n";
    };
    $end = function ($component, $installing, $verbose) {
        echo "Done: $component\n";
    };

    echo "Installing missing plugins...\n\n";

    foreach (array_keys(core_component::get_plugin_types()) as $type) {
        upgrade_plugins($type, $start, $end, true);
    }

    unset_config('upgraderunning');

    echo "\nUpdating version hash and serialised DB state...\n";

    $reflection = new ReflectionClass(\core\test\phpunit\phpunit_util::class);

    $storeHash = $reflection->getMethod('store_versions_hash');
    $storeHash->setAccessible(true);
    $storeHash->invoke(null);

    $storeState = $reflection->getMethod('store_database_state');
    $storeState->setAccessible(true);
    $storeState->invoke(null);

    echo "Done.\n";

    // Overwrite the snapshot with the updated state so the next run only installs
    // plugins added since this run, not everything since the original baseline.
    echo "Updating snapshot at $snapshotdir...\n";
    exec("rm -rf $snapshotdir/phpunitdata");
    exec("cp -r {$CFG->phpunit_dataroot} $snapshotdir/phpunitdata", $out, $exitcode);
    if ($exitcode !== 0) {
        echo "Warning: failed to update snapshot.\n";
    } else {
        echo "Snapshot updated.\n";
    }
}