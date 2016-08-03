<?php
/**
 * Exports Functions
 *
 * These are functions are used for exporting data from Easy Digital Downloads.
 *
 * @package     Give
 * @subpackage  Admin/Export
 * @copyright   Copyright (c) 2016, WordImpress
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once GIVE_PLUGIN_DIR . 'includes/admin/reporting/class-export.php';

/**
 * Exports earnings for a specified time period
 * Give_Earnings_Export class.
 *
 * @since 1.0
 * @return void
 */
function give_export_earnings() {
	require_once GIVE_PLUGIN_DIR . 'includes/admin/reporting/class-export-earnings.php';

	$earnings_export = new Give_Earnings_Export();

	$earnings_export->export();
}

add_action( 'give_earnings_export', 'give_export_earnings' );

/**
 * Exports all the payments stored in Payment History to a CSV file using the
 * Give_Export class.
 *
 * @since 1.0
 * @return void
 */
function give_export_payment_history() {
	require_once GIVE_PLUGIN_DIR . 'includes/admin/reporting/class-export-payments.php';

	$payments_export = new Give_Payments_Export();

	$payments_export->export();
}

add_action( 'give_payment_export', 'give_export_payment_history' );

/**
 * Export all the donors to a CSV file.
 *
 * Note: The WordPress Database API is being used directly for performance
 * reasons (workaround of calling all posts and fetch data respectively)
 *
 * @since 1.0
 * @return void
 */
function give_export_all_donors() {
	require_once GIVE_PLUGIN_DIR . 'includes/admin/reporting/class-export-customers.php';

	$donor_export = new Give_Donors_Export();

	$donor_export->export();
}

add_action( 'give_email_export', 'give_export_all_donors' );

//***************************************************************
// Functions for exporting a csv of user IDs and their respective campaigns

function download_send_headers($filename) {
	// disable caching
	$now = gmdate("D, d M Y H:i:s");
	//header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
	header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
	header("Last-Modified: {$now} GMT");

	// force download
	header("Content-Type: application/force-download");
	header("Content-Type: application/octet-stream");
	header("Content-Type: application/download");

	// disposition / encoding on response body
	header("Content-Disposition: attachment;filename={$filename}");
	header("Content-Transfer-Encoding: binary");
}

function array2csv(array &$array,$col_names) {
	if (count($array) == 0) {
		return null;
	}
	ob_start();
	echo "\xEF\xBB\xBF"; // UTF-8 BOM
	$df = fopen("php://output", 'w');
	fputcsv($df, $col_names);
	foreach ($array as $row) {
		fputcsv($df, $row);
	}
	fclose($df);
	return ob_get_clean();
}

function find_post($user_id) {
	$args = array(
		'post_type' => 'give_forms',
		'author'        => $user_id,
		'tax_query'     => array(
			'relation'  => 'AND',
			array(
				'taxonomy'  => 'give_forms_category',
				'field'     => 'slug',
				'terms'     => 'active'
			),
			array(
				'taxonomy'  => 'give_forms_category',
				'field'     => 'slug',
				'terms'     => date('Y'),
			)
		),
		'orderby'       => 'post_date',
		'order'         => 'DESC'
	);
	return get_posts( $args);

}
function export_users() {
	$array = array();
	$query = get_users(array('fields'=>array('ID','display_name')));
	foreach($query as $user) {
		$array[] = array(get_the_title(find_post($user->ID)),$user->ID);
	}
	download_send_headers("data_export_" . date("Y-m-d") . ".csv");
	echo array2csv($array,array('User ID','Campaign Name'));
	die();
}

add_action('give_campaign_export','export_users');
//***************************************************************
//Functions for exporting a csv of various participant and team stats

function parts2csv(array &$array) {
	if (count($array) == 0) {
		return null;
	}
	ob_start();
	echo "\xEF\xBB\xBF"; // UTF-8 BOM
	$df = fopen("php://output", 'w');
	foreach ($array as $row) {
		fputcsv($df, $row);
	}
	fclose($df);
	return ob_get_clean();
}

//calculate stats for indivdual participants not part of a team
function format_ind_stats() {
	$array = array();

	//Section Title
	$array[] = array('INDIVIDUALS');

	$args = array(
		'post_type' => 'give_forms',
		'tax_query' => array(
			'relation' => 'AND',
			array(
				'taxonomy' => 'give_forms_category',
				'field' => 'slug',
				'terms' => date('Y'),
			),
			array(
				'taxonomy' => 'give_forms_category',
				'field' => 'slug',
				'terms' => 'active',
			),
		)
	);
	$camps = get_posts($args);

	$total = 0; //Grand total
	
	foreach ($camps as $camp) {
		$frm = new Give_Donate_Form($camp->ID);
		$total += $frm->get_earnings(); // piggybacking to calculate grand total of all campaign earnings

		if (!get_the_terms($camp,'give_forms_tag')) { // if no tags(ie not part of a team)
			$array[] = array('',$camp->post_title . ' (ID: ' . $camp->post_author . ')', '$' . $frm->get_earnings());
		}

	}
	$array[] = array();
	$array[] = array('GRAND TOTAL','$'.$total);
	return $array;
}

//calculate and return stats for teams and their members
function format_team_stats($teams) {
	$array = array();

	//Section Title
	$array[] = array('TEAMS');

	//iterate through list of $teams
	foreach ($teams as $team) {
		$args = array(
			'post_type' => 'give_forms',
			'tax_query' => array(
				'relation' => 'AND',
				array(
					'taxonomy' => 'give_forms_category',
					'field' => 'slug',
					'terms' => date('Y'),
				),
				array(
					'taxonomy' => 'give_forms_category',
					'field' => 'slug',
					'terms' => 'active',
				),
				array(
					'taxonomy' => 'give_forms_tag',
					'field' => 'name',
					'terms' => $team,
				)
			)
		);
		$members = get_posts($args);

		if (count($members) !=0) { // don't output if this team has no members
			//Team member count
			$array[] = array($team, count($members) . ((count($members) == 1)? ' member':' members'));

			$team_income = 0;

			foreach ($members as $member) {
				$frm = new Give_Donate_Form($member->ID);
				$array[] = array('',$member->post_title . ' (ID: ' . $member->post_author . ')', '$' . $frm->get_earnings());
				$team_income += $frm->get_earnings(); //aggregating earnings of all team members
			}

			$array[] = array();
			$array[] = array('', 'Team Total', '$' . $team_income);
			$array[] = array();
		}
	}
	return $array;
}

//
function export_participant_stats() {
	$array = array();

	$query = find_post('');
	$num_participants = count($query)-1;
	$array[] = array('# of Participants:',$num_participants);

	$teams = get_terms( array(
		'taxonomy' => 'give_forms_tag',
		'fields'	=> 'names',
		'hide_empty' => true,
	));
	$array[] = array('# of Teams:',count($teams));

	//calculate stats for teams and their members
	$team_stats = format_team_stats($teams);
	foreach ($team_stats as $team_stat) {
		$array[] = $team_stat;
	}

	//calculate stats for individuals not part of a team
	$ind_stats = format_ind_stats();
	foreach ($ind_stats as $ind_stat) {
		$array[] = $ind_stat;
	}

	//export and exit
	download_send_headers("data_export_" . date("Y-m-d") . ".csv");
	echo parts2csv($array);
	die();
}
add_action('give_participant_stats_export','export_participant_stats');