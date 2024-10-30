<?php

/**
 * Class for displaying a list of iATS Forms.
 */
class iATS_Form_Table extends WP_List_Table {

	/**
	 * Constructor, we override the parent to pass our own arguments
	 * We usually focus on three parameters: singular and plural labels, as well
	 * as whether the class supports AJAX.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'wp_form_table_value', // Singular label.
				'plural'   => 'wp_form_table_values', // Plural label, also this well be one of the table css class.
				'ajax'     => false, // We won't support Ajax for this table.
			)
		);
	}

	/**
	 * Define the columns that are going to be used in the table
	 *
	 * @return array $columns, the array of columns to use with the table
	 */
	public function get_columns() {
		return array(
			'id'      => "<span  title='This is setup automatically and is the unique identifier for each form'>" . __( 'Form ID' ) . '</span>',
			'title'   => "<span  title='This form name is for internal purposes only and will NOT be shown on the form on your site'>" . __( 'Form Name' ) . '</span>',
			'content' => "<span  title='This shortcode can be copy-pasted into your post to display the form on your site.'>" . __( 'Shortcode' ) . '</span>',
			'action'  => __( 'Action' ),
		);
	}

	/**
	 * Decide which columns to activate the sorting functionality on
	 *
	 * @return array $sortable, the array of columns that can be sorted by the
	 *     user
	 */
	public function get_sortable_columns() {
		return array(
			'id'    => array( 'id', true ),
			'title' => array( 'title', true ),
		);

	}

	public function process_actions() {
		if ( ! current_user_can( 'administrator' ) ) {
			return;
		}

		// Handle adding a new form.
		if ( isset( $_POST['submit-form-add'] ) && check_admin_referer( 'iats-form' ) ) {
			$title = sanitize_text_field( wp_unslash( filter_input( INPUT_POST, 'title', FILTER_SANITIZE_STRING ) ) );
			if ( ! $title ) {
				die( 'Form Name field is required' );
			}

			$content = htmlspecialchars( wp_unslash( filter_input( INPUT_POST, 'content' ) ) );
			if ( ! $content ) {
				die( 'iATS Embed Code field is required' );
			}

			global $wpdb;
			$wpdb->insert(
				"{$wpdb->prefix}aura_forms",
				array(
					'title'   => $title,
					'content' => $content,
				)
			);

			$wpdb->show_errors();
			echo '<div class="updated"><p>Your form has been added.</p></div>';
		}

		// Handle editing a form.
		if ( isset( $_POST['submit-form-edit'] ) && check_admin_referer( 'iats-form' ) ) {
			$id = sanitize_text_field( wp_unslash( filter_input( INPUT_POST, 'editID', FILTER_SANITIZE_STRING ) ) );
			if ( ! $id ) {
				die( 'Invalid form request' );
			}

			$title = sanitize_text_field( wp_unslash( filter_input( INPUT_POST, 'title', FILTER_SANITIZE_STRING ) ) );
			if ( ! $title ) {
				die( 'Form Name field is required' );
			}

			$content = htmlspecialchars( wp_unslash( filter_input( INPUT_POST, 'content' ) ) );
			if ( ! $content ) {
				die( 'iATS Embed Code field is required' );
			}

			global $wpdb;
			$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}aura_forms SET title = %s, content = %s WHERE id = %d", $title, $content, $id ) );

			echo '<div class="updated"><p>Your form has been updated.</p></div>';
		}

		// Handle deleting a form.
		if ( isset( $_POST['submit-form-delete'] ) && check_admin_referer( 'iats-form' ) ) {
			$id = sanitize_text_field( wp_unslash( filter_input( INPUT_POST, 'deleteID', FILTER_SANITIZE_STRING ) ) );
			if ( ! $id ) {
				die( 'Invalid form request' );
			}

			global $wpdb;

			$wpdb->delete( "{$wpdb->prefix}aura_forms", array( 'id' => $id ) );

			echo '<div class="updated"><p>Your form has been deleted.</p></div>';
		}

		echo '<style>
			.edit-table td {
				padding-bottom: 16px;
			}
			.edit-table td:first-child {
				vertical-align: top;
				text-align: right;
				padding-top: 6px;
				white-space: nowrap;
			}
			.edit-table input,
			.edit-table textarea {
				width: 100%;
			}
			.edit-table-description {
				font-size: 12px;
				color: #555555;
			}
			@media (min-width: 960px) {
				.edit-table {
					width: 50%;
				}
			}
		</style>';

		$wp_form_table_value = sanitize_text_field( wp_unslash( filter_input( INPUT_GET, 'wp_form_table_value', FILTER_SANITIZE_STRING ) ) );

		if ( 'delete' === $this->current_action() && $wp_form_table_value ) {
			global $wpdb;

			$results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}aura_forms WHERE id = %d", $wp_form_table_value ) );

			if ( ! $results || count( $results ) <= 0 ) {
				die( 'Invalid form request' );
			}

			$form = $results[0];
			?>
			<h1> Delete <?php echo esc_html( $form->title ); ?> </h1>
			<form method="POST" name="edit_form" action="?page=load-forms">
				<p>Are you sure? This will delete the form permanently.</p>
				<input name="deleteID" type="hidden" value='<?PHP echo esc_attr( $form->id ); ?>'>
				<?php wp_nonce_field( 'iats-form' ); ?>
				<input type="submit" name="submit-form-delete" class="pull-right" value='Delete'>
			</form>
			<?php
			die();
		} elseif ( 'edit' === $this->current_action() && $wp_form_table_value ) {
			global $wpdb;

			$results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}aura_forms WHERE id = %d", $wp_form_table_value ) );

			if ( ! $results || count( $results ) <= 0 ) {
				die( 'Invalid form request' );
			}

			$form = $results[0];
			?>
			<h1> Edit Form </h1>
			<form method="POST" name="edit_form" action="?page=load-forms">
			<table class="edit-table">
				<tr> <td><label for="edit-form-id">Form ID:</label></td> <td><input id="edit-form-id" name="editID" type="text" value='<?PHP echo esc_attr( $form->id ); ?>' readonly> </td></tr>
				<tr> <td><label for="edit-form-title">Form Name:</label></td> <td>
					<input id="edit-form-title" aria-describedby="edit-form-title-description" name="title" type="text" value='<?PHP echo esc_attr( $form->title ); ?>' required>
					<div id="edit-form-title-description" class="edit-table-description">This form name is for internal purposes only and will NOT be shown on the form on your site.</div>
					</td> </tr>
				<tr> <td><label for="edit-form-content">iATS Embed Code:</label></td> <td>
					<textarea id="edit-form-content" aria-describedby="edit-form-content-description" name="content" rows="4" required><?PHP echo esc_html( stripslashes( $form->content ) ); ?></textarea>
					<div id="edit-form-content-description" class="edit-table-description">The Embed Code script can be found in the iATS portal. Please sign in to your iATS account and copy/paste the embed code of the form you would like to use.</div>
					</td> </tr>
			</table>
			<?php wp_nonce_field( 'iats-form' ); ?>
			<input type="submit" name="submit-form-edit" class="pull-right" value='Save'>
		</form>
			<?php
			die();
		} elseif ( 'add' === $this->current_action() ) {
			?>
			<h1> Add Form </h1>
			<form method="POST" name="edit_form" action="?page=load-forms">
			<table class="edit-table">
				<tr> <td><label for="edit-form-title">Form Name:</label></td> <td>
				<input id="edit-form-title" aria-describedby="edit-form-title-description" name="title" type="text" value='' required>
				<div id="edit-form-title-description" class="edit-table-description">This form name is for internal purposes only and will NOT be shown on the form on your site.</div>
			</td></tr>
				<tr> <td><label for="edit-form-content">iATS Embed Code:</label></td> <td>
				<textarea id="edit-form-content" aria-describedby="edit-form-content-description" name="content" rows="4" required></textarea>
				<div id="edit-form-content-description" class="edit-table-description">The Embed Code script can be found in the iATS portal. Please sign in to your iATS account and copy/paste the embed code of the form you would like to use.</div>
			</td></tr>
			</table><br/><br/>
			<?php wp_nonce_field( 'iats-form' ); ?>
			<input type="submit" name="submit-form-add" class="button action pull-right" value='Add'>
			</form>
			<?php
			die();
		}

	}

	/**
	 * Prepare the table with different parameters, pagination, columns and
	 * table elements
	 */
	public function prepare_items() {
		global $wpdb;

		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->process_actions();

		$search_term = sanitize_text_field( filter_input( INPUT_POST, 's', FILTER_SANITIZE_STRING ) );

		$query = "SELECT * FROM {$wpdb->prefix}aura_forms WHERE title LIKE '%$search_term%' ";

		// Parameters that are going to be used to order the result.
		$orderby = sanitize_text_field( filter_input( INPUT_GET, 'orderby', FILTER_SANITIZE_STRING ) );
		$order   = sanitize_text_field( filter_input( INPUT_GET, 'order', FILTER_SANITIZE_STRING ) );

		if ( ! empty( $orderby ) & ! empty( $order ) ) {
			$query .= ' ORDER BY ' . $orderby . ' ' . $order;
		}

		// Number of elements in your table?
		$totalitems = $wpdb->query( $query ); // Return the total number of affected rows.
		// How many to display per page?
		$perpage = 50;
		// Which page is this?
		$paged = sanitize_text_field( filter_input( INPUT_GET, 'paged', FILTER_SANITIZE_STRING ) );

		if ( empty( $paged ) || ! is_numeric( $paged ) || $paged <= 0 ) {
			$paged = 1;
		}
		// How many pages do we have in total?
		$totalpages = ceil( $totalitems / $perpage );

		// Adjust the query to take pagination into account.
		if ( ! empty( $paged ) && ! empty( $perpage ) ) {
			$offset = ( $paged - 1 ) * $perpage;
			$query .= ' LIMIT ' . (int) $offset . ',' . (int) $perpage; }

		$this->set_pagination_args(
			array(
				'total_items' => $totalitems,
				'total_pages' => $totalpages,
				'per_page'    => $perpage,
			)
		);

		$this->items = $wpdb->get_results( $query );

	}

	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'id':
				return $item->id ? "<span  title='This is setup automatically and is the unique identifier for each form'>" . esc_html( $item->id ) . '</span>' : '';
			case 'title':
				return $item->title ? "<span  title='This form name is for internal purposes only and will NOT be shown on the form on your site'>" . esc_html( $item->title ) . '</span>' : '';
			case 'content':
				return $item->id ? '<span  title="This shortcode can be copy-pasted into your post to display the form on your site.">[aura-form id="' . esc_html( $item->id ) . '"]</span>' : '';
			case 'action':
				$page = sanitize_text_field( filter_input( INPUT_GET, 'page', FILTER_SANITIZE_STRING ) );
				return current_user_can( 'administrator' ) ? '<a href="?page=' . esc_attr( $page ) . '&action=edit&wp_form_table_value=' . esc_attr( $item->id ) . '">Edit</a> | <a href="?page=' . esc_attr( $page ) . '&action=delete&wp_form_table_value=' . esc_attr( $item->id ) . '">Delete</a>' : '-';
			default:
				return '';
		}
	}

}
