<?php

/**
 * Forum Actions
 * Adds one action to the database
 *
 * @package   forum_actions
 * @copyright 2014 Moodle Pty Ltd (http://moodle.com)
 * @author    Mikhail Janowski <mikhail@getsmarter.co.za>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot.'/local/mention_users/lib.php');

$reply_id = required_param('reply', PARAM_INT); // Forum post ID
error_log("geciiiiiiiasdjlfliasdjfilj");
error_log(print_r($p,1));
error_log("replyiddddd");
//To do: check capability
//$context = context_module::instance($cm->id);
//require_capability('mod/forum:replypost', $context);

$result = new stdClass();
$result->result = false; // set in case uncaught error happens
$result->content = 'Unknown error';

//Only allow to add action if logged in
if(isloggedin()) {

	if($action == 'like' || $action == 'thanks') {
		// Insert new action record
		$record = new stdClass();
		$record->postid = $p;
		$record->userid = $USER->id;
		$record->action = $action;
		$record->created = time();

		$actionid = $DB->insert_record('forum_actions', $record, true);

		//Get post to return
		$sql = "
		SELECT
		    p.id
		FROM
		    {forum_posts} p
		WHERE
		    p.id = $p
		";

		$post = $DB->get_records_sql($sql);

		populatePostActions($post);

		$result->result = true;
		$result->content = $post;

	}
	else {
		$result->result = false;
		$result->content = 'Invalid action';
	}
}
else {
	$result->result = false;
	$result->content = 'Your session has timed out. Please login again.';
}

header('Content-type: application/json');
echo json_encode($result);
//echo '<pre>'.print_r($actionid, true).'</pre>';

