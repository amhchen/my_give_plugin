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
	header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
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

function array2csv(array &$array) {
	if (count($array) == 0) {
		return null;
	}
	ob_start();
	$df = fopen("php://output", 'w');
	fputcsv($df, array('User ID','Campaign Name'));
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
	$camps = get_posts( $args);
	if (count($camps) == 0) {return '';}
	else {
		return get_the_title($camps[0]->ID);
	}
}
function export_users() {
	$array = array();
	$query = get_users(array('fields'=>array('ID','display_name')));
	foreach($query as $user) {
		$array[] = array(find_post($user->ID),$user->ID);
	}
	download_send_headers("data_export_" . date("Y-m-d") . ".csv");
	echo array2csv($array);
	die();
}

add_action('give_campaign_export','export_users');
//***************************************************************