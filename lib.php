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
 * @package    block_quickmailjpn
 * @copyright  2008-2017 Louisiana State University
 * @copyright  2008-2017 Adam Zapletal, Chad Mazilly, Philip Cali, Robert Russo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

abstract class quickmailjpn
{

    /**
     * @const string The page type for this block.
     */
    const PAGE_TYPE = 'block-quickmailjpn';
    static function zip_attachments($context, $table, $id) {
        global $CFG, $USER;

        $base_path = "block_quickmailjpn/{$USER->id}";
        $moodle_base = "$CFG->tempdir/$base_path";

        if (!file_exists($moodle_base)) {
            mkdir($moodle_base, $CFG->directorypermissions, true);
        }

        $zipname = "attachment.zip";
        $actual_zip = "$moodle_base/$zipname";

        $fs = get_file_storage();
        $packer = get_file_packer();

        $files = $fs->get_area_files(
            $context->id,
            'block_quickmailjpn',
            'attachment_' . $table,
            $id,
            'id'
        );

        $stored_files = array();
        foreach ($files as $file) {
            if ($file->is_directory() and $file->get_filename() == '.') {
                continue;
            }

            $stored_files[$file->get_filepath().$file->get_filename()] = $file;
        }

        $packer->archive_to_pathname($stored_files, $actual_zip);

        return $actual_zip;
    }

}
function block_quickmailjpn_pluginfile($course, $record, $context, $filearea, $args, $forcedownload) {
    $fs = get_file_storage();
    global $DB, $CFG;

    if (!empty($CFG->block_quickmail_downloads) && $filearea != 'log') {
        require_course_login($course, true, $record);
    }

    list($itemid, $filename) = $args;

    if ($filearea == 'attachment_log') {
        $time = $DB->get_field('block_quickmailjpn_log', 'timesent', array(
            'id' => $itemid
        ));

        if ("{$time}_attachments.zip" == $filename) {
            $path = quickmailjpn::zip_attachments($context, 'log', $itemid);
            send_temp_file($path, 'attachments.zip');
        }
    }

    $params = array(
        'component' => 'block_quickmailjpn',
        'filearea' => $filearea,
        'itemid' => $itemid,
        'filename' => $filename
    );

    $instanceid = $DB->get_field('files', 'id', $params);

    if (empty($instanceid)) {
        send_file_not_found();
    } else {
        $file = $fs->get_file_by_id($instanceid);
        send_stored_file($file);
    }
}
