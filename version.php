<?php
// This file is part of CodeRunnerEx.
//
// CodeRunnerEx is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// CodeRunnerEx is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with CodeRunnerEx. If not, see <http://www.gnu.org/licenses/>.

/**
 * @package   qtype_coderunnerex
 * @copyright Ginger Jiang, China Pharmerutical University.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version  = 2024103000;
$plugin->requires = 2022041900;
$plugin->cron = 0;
$plugin->component = 'qtype_coderunnerex';
$plugin->maturity = MATURITY_RC;
$plugin->release = '0.1.0';

$plugin->dependencies = array(
    'qtype_coderunner' => 2022082201,
    'qbehaviour_adaptive_adapted_for_coderunner' => 2021112300,
);

