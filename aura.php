<?php
/**
 * iATS Online Forms
 *
 * @package           iats-online-forms
 * @author            iATS Payments
 *
 * @wordpress-plugin
 * Plugin Name:       iATS Online Forms
 * Description:       A plugin to help you create and embed your Aura forms. Use a Gutenberg block or shortcode [aura-form id="21"] to display the form on your page. Please note, you must be an existing iATS customer to use this plugin.
 * Version:           1.2
 * Author:            iATS Payments
 * Author URI:        https://www.iatspayments.com/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.0
 * Requires PHP:      5.6
 */

defined( 'ABSPATH' ) || die( 'Insecure script call' );

const IATS_VERSION = '1.2';

require_once ABSPATH . 'wp-admin/includes/upgrade.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
require_once plugin_dir_path( __FILE__ ) . 'class-iats-form-table.php';

function iats_plugin_launch() {
	global $wpdb;

	$charset_collate = $wpdb->get_charset_collate();

	$queries[] = "CREATE TABLE {$wpdb->prefix}aura_forms (
	  id int(11) NOT NULL AUTO_INCREMENT,
	  title varchar(250) NOT NULL,
	  content TEXT NOT NULL,
	  created_at timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL,
	  PRIMARY KEY  (id)
	) $charset_collate;";

	dbDelta( $queries );
}

register_activation_hook( __FILE__, 'iats_plugin_launch' );

function iats_aura_form_register_block() {
	$deps = array(
		'wp-plugins',
		'wp-blocks',
		'wp-block-editor',
		'wp-editor',
		'wp-edit-post',
		'wp-element',
		'wp-components',
	);
	wp_enqueue_script( 'aura-block-editor', plugins_url( 'js/aura-block-editor.js', __FILE__ ), $deps, IATS_VERSION, true );

	global $wpdb;

	$query = "SELECT id, content, title FROM {$wpdb->prefix}aura_forms";
	$forms = $wpdb->get_results( $query );

	$available_forms = array();

	if ( $forms ) {
		foreach ( $forms as $form ) {
			$available_forms[] = array(
				'value' => $form->id,
				'label' => $form->title,
			);
		}
	}

	$stored_data = array( 'available_forms' => $available_forms );

	wp_localize_script( 'aura-block-editor', 'aura_ajax_object', $stored_data );
}

add_action( 'enqueue_block_editor_assets', 'iats_aura_form_register_block', 9 );

function iats_display_aura_form( $id ) {
	global $wpdb;

	$query = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}aura_forms WHERE id = %d ", array( $id ) );
	$forms = $wpdb->get_results( $query );

	if ( $forms ) {
		$script = $forms[0]->content;
		return stripslashes( htmlspecialchars_decode( $script ) );
	} elseif ( current_user_can( 'administrator' ) ) {
		return 'Invalid Form loaded (' . esc_html( $id ) . ')';
	}
	return '';
}

function iats_admin_page_load_forms() {
	global $wpdb;

	$wpdb->show_errors();
	// Prepare Table of elements.
	$wp_form_table = new iATS_Form_Table();
	$wp_form_table->prepare_items();

	echo '<div class="wrap">';

	echo '<style>
		.table_form td{
			width: 250px;
		    white-space: nowrap;
		    overflow: hidden;
		    text-overflow: ellipsis;
		}
	</style>';

	$page = sanitize_text_field( filter_input( INPUT_GET, 'page', FILTER_SANITIZE_STRING ) );

	echo '<h1>iATS Online Forms</h1>';

	if ( current_user_can( 'administrator' ) ) {
		echo '<a href="?page=' . esc_attr( $page ) . '&action=add" class="button action">Add New</a>';
	}

	echo '<form method="POST" name="search_form" action="?page=load-forms">';
	$wp_form_table->search_box( 'Search', 'search' );
	echo '</form>';

	if ( $wp_form_table->has_items() ) {
		echo '<p>To add a form to your site: edit a page or post, click ‘Add block’ and search for ‘iATS Online Form’.</p>';
	}

	// Table of elements.
	echo '<form method="GET" class="table_form" name="table_form" >';
	echo '<input type="hidden" name="page" value="' . esc_attr( $page ) . '"/>';
	$wp_form_table->display();
	echo '</form>';

	echo '<div class="clear"></div>';
	echo '</div>';
}

add_action( 'wp_ajax_nopriv_admin_page_load_forms', 'iats_admin_page_load_forms' );
add_action( 'wp_ajax_admin_page_load_forms', 'iats_admin_page_load_forms' );

function iats_aura_shortcode( $atts ) {
	$args = shortcode_atts(
		array(
			'id' => '',
		),
		$atts
	);

	$id = (int) $args['id'];

	return iats_display_aura_form( $id );
}

add_shortcode( 'aura-form', 'iats_aura_shortcode' );

function iats_plugin_remove() {
	global $wpdb;

	$sql = "DROP TABLE IF EXISTS {$wpdb->prefix}aura_forms;";
	$wpdb->query( $sql );
}

register_deactivation_hook( __FILE__, 'iats_plugin_remove' );

function iats_aura_admin_menu() {
	add_menu_page( 'iATS Online Forms', 'iATS Online Forms', 'edit_posts', 'load-forms', 'iats_admin_page_load_forms', 'dashicons-tablet' );
	add_submenu_page( 'load-forms', 'Forms', 'Forms', 'edit_posts', 'load-forms', 'iats_admin_page_load_forms' );
}

add_action( 'admin_menu', 'iats_aura_admin_menu' );
