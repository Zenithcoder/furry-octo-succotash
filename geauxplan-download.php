<?php
/*
  Plugin Name: Geauxplan Download
  Plugin URI:
  Description: custom geauxplan download
  Author: thedigitalnavigator
  Version: 1.1.0
  Author URI: http://thedigitalnavigator.com/
 */


register_activation_hook( __FILE__, 'geauxplan_activation' );

function geauxplan_activation() {
	// Get access to global database access class
	global $wpdb;

	// Create table on main blog in network mode or single blog
	geauxplan_create_table( $wpdb->get_blog_prefix() );
}

// Function to create new database table
function geauxplan_create_table( $prefix ) {
	// Prepare SQL query to create database table
	// using received table prefix
	$creation_query =
		'CREATE TABLE ' . $prefix . 'geauxplan(
			`geauxplan_id` int(20) NOT NULL AUTO_INCREMENT,
			`geauxplan_url` text,
			`geauxplan_username` varchar(255) DEFAULT NULL,
			`geauxplan_date` date DEFAULT NULL,
			PRIMARY KEY (`geauxplan_id`)
			);';

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $creation_query );
}

// Register function to be called when admin menu is constructed
add_action( 'admin_menu', 'geauxplan_settings_menu' );

// Add new menu item under Settings menu for Bug Tracker
function geauxplan_settings_menu() {
	add_options_page( 'Geauxplan Download Management',
		'GeauxPlan Download', 'manage_options',
		'geauxplan-download',
		'geauxplan_config_page' );
}

// Function to render plugin admin page
function geauxplan_config_page() {
	global $wpdb;
	?>
	<!-- Top-level menu -->
	<div id="ch8bt-general" class="wrap">
	<h2>Geauxplan Download
		<a class="add-new-h2" 
			href="<?php echo add_query_arg( array ( 'page' => 'geauxplan-download', 'id' => 'new' ), admin_url( 'options-general.php' ) ); ?>">Add New Downlaod(pdf)</a></h2>
		
	<!-- Display bug list if no parameter sent in URL -->
	<?php if ( empty( $_GET['id'] ) ) { 
		$geauxplan_query = 'select * from ' . $wpdb->get_blog_prefix();
		$geauxplan_query .= 'geauxplan ORDER by geauxplan_date DESC';
		$geauxplans_items = $wpdb->get_results( $geauxplan_query, ARRAY_A );
	?>

	<h3>Manage Download Entries</h3>
	<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
	<input type="hidden" name="action" value="delete_geauxplan" />

	<!-- Adding security through hidden referrer field -->
	<?php wp_nonce_field( 'geauxplan_deletion' ); ?>
	
	<table class="wp-list-table widefat fixed" >
	<thead><tr><th style="width: 50px"></th><th style="width: 80px">ID</th>
	<th style=width:300px>URL</th><th>Username</th></tr></thead>

	<?php 
		// Display bugs if query returned results
		if ( $geauxplans_items ) {
			foreach ( $geauxplans_items as $geauxplans_item ) {
				echo '<tr style="background: #FFF">';
				echo '<td><input type="checkbox" name="geauxplan[]" value="';
				echo esc_attr( $geauxplans_item['geauxplan_id'] ) . '" /></td>';
				echo '<td>' . $geauxplans_item['geauxplan_id'] . '</td>';
				echo '<td><a href="' . add_query_arg( array( 'page' => 'geauxplan-download', 'id' => $geauxplans_item['geauxplan_id'] ), admin_url( 'options-general.php' ) );
				echo '">' . $geauxplans_item['geauxplan_url'] . '</a></td>';
				echo '<td>' . $geauxplans_item['geauxplan_username'] . '</td></tr>';
			}
		} else {
			echo '<tr style="background: #FFF">';
			echo '<td colspan="4">No downloads Found</td></tr>';
		}
	?>
	</table><br />
	
	<input type="submit" value="Delete Selected" class="button-primary"/>
	</form>

	<?php } elseif ( isset( $_GET['id'] ) && ( 'new' == $_GET['id'] || is_numeric( $_GET['id'] ) ) ) {

	// Display bug creation and editing form if bug is new
	// or numeric id was sen       
	$geauxplan_id = intval( $_GET['id'] );
	$mode = 'new';

	// Query database if numeric id is present
	if ( $geauxplan_id > 0 ) {
		$geauxplan_query = 'select * from ' . $wpdb->get_blog_prefix();
		$geauxplan_query .= 'geauxplan where geauxplan_id = %d';

		$geauxplan_data = $wpdb->get_row( $wpdb->prepare( $geauxplan_query, $geauxplan_id ), ARRAY_A );

		if ( $geauxplan_data ) {
			$mode = 'edit';
		}
	}
	
	if ( 'new' == $mode ) {
        $geauxplan_data = array(
            'geauxplan_url' => '',
            'geauxplan_username' => ''
        ); 
    }

	// Display title based on current mode
	if ( 'new' == $mode ) {
		echo '<h3>Add New Download</h3>';
	} elseif ( 'edit' == $mode ) {
		echo '<h3>Edit Download #' . $geauxplan_data['geauxplan_id'] . ' - ';
		echo $geauxplan_data['geauxplan_url'] . '</h3>';
	}
	?>

	<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
	<input type="hidden" name="action" value="save_geauxplan" />
	<input type="hidden" name="geauxplan_id" value="<?php echo $geauxplan_id; ?>" />

	<!-- Adding security through hidden referrer field -->
	<?php wp_nonce_field( 'geauxplan_add_edit' ); ?>

	<!-- Display bug editing form, with previous values if available -->
	<table>
		<tr>
			<td style="width: 150px">URL</td>
			<td><input type="text" name="geauxplan_url" size="60" value="<?php echo esc_html( $geauxplan_data['geauxplan_url'] ); ?>"/></td>
		</tr>
		<tr>
			<td>Username</td>
			<td><textarea name="geauxplan_username" cols="60"><?php echo esc_textarea( $geauxplan_data['geauxplan_username'] ); ?></textarea></td>
		</tr>
		 
	</table>
	<input type="submit" value="Submit" class="button-primary"/>
	</form>

	<?php } ?>
	</div>
<?php }



// Register function to be called when administration pages init takes place
add_action( 'admin_init', 'geauxplan_admin_init' );

// Register functions to be called when bugs are saved
function geauxplan_admin_init() {
	add_action('admin_post_save_geauxplan',
		'process_geauxplan');

	add_action('admin_post_delete_geauxplan',
		'delete_geauxplan');
}

// Function to be called when new bugs are created or existing bugs
// are saved
function process_geauxplan() {
	// Check if user has proper security level
	if ( !current_user_can( 'manage_options' ) ) {
		wp_die( 'Not allowed' );
	}

	// Check if nonce field is present for security
	check_admin_referer( 'geauxplan_add_edit' );
	global $wpdb;

	// Place all user submitted values in an array
	$geauxplan_data = array();
	$geauxplan_data['geauxplan_url'] = ( isset( $_POST['geauxplan_url'] ) ? sanitize_text_field( $_POST['geauxplan_url'] ) : '' );
	$geauxplan_data['geauxplan_username'] = ( isset( $_POST['geauxplan_username'] ) ? sanitize_text_field( $_POST['geauxplan_username'] ) : '' );
	 
	// Set geauxplan report date as current date
	$geauxplan_data['geauxplan_date'] = date( 'Y-m-d' );

	// Call the wpdb insert or update method based on value
	// of hidden bug_id field
	if ( isset( $_POST['geauxplan_id'] ) && 0 == $_POST['geauxplan_id'] ) {
		$wpdb->insert($wpdb->get_blog_prefix() . 'geauxplan', $geauxplan_data );
	} elseif ( isset( $_POST['geauxplan_id'] ) && $_POST['geauxplan_id'] > 0 ) {
		$wpdb->update( $wpdb->get_blog_prefix() . 'geauxplan', $geauxplan_data, array( 'geauxplan_id' => $_POST['geauxplan_id'] ) );
	}

	// Redirect the page to the admin form
	wp_redirect( add_query_arg( 'page', 'geauxplan-download', admin_url( 'options-general.php' ) ) );
	exit;
}

// Function to be called when deleting bugs
function delete_geauxplan() {
	// Check that user has proper security level
	if ( !current_user_can( 'manage_options' ) ) {
		wp_die( 'Not allowed' );
	}

	// Check if nonce field is present
	check_admin_referer( 'geauxplan_deletion' );

	// If bugs are present, cycle through array and call SQL
	// command to delete entries one by one
	if ( !empty( $_POST['geauxplan'] ) ) {
		// Retrieve array of bugs IDs to be deleted
		$geauxplans_to_delete = $_POST['geauxplan'];

		global $wpdb;

		foreach ( $geauxplans_to_delete as $geauxplan_to_delete ) {
			$query = 'DELETE from ' . $wpdb->get_blog_prefix() . 'geauxplan ';
			$query .= 'WHERE geauxplan_id = %d';
			$wpdb->query( $wpdb->prepare( $query, intval( $geauxplan_to_delete ) ) );
		}
	}

	// Redirect the page to the admin form
	wp_redirect( add_query_arg( 'page', 'geauxplan-download', admin_url( 'options-general.php' ) ) );
	exit;
}

// Define new shortcode and specify function to be called when found
add_shortcode( 'geauxplan-list', 'geauxplan_shortcode_list' );

// Shortcode implementation function
function geauxplan_shortcode_list() {
	global $wpdb;

	 // Prepare query to retrieve bugs from database
	$geauxplan_query = 'select * from ' . $wpdb->get_blog_prefix();
	$geauxplan_query .= 'geauxplan';

	// Add search string in query if present
    $current_user = wp_get_current_user();
  /*  $search_string  = $current_user->user_login;
		$search_term = '%' . $search_string . '%';
		$geauxplan_query .= "where geauxplan_username like '%s' ";
	$geauxplan_query .= 'ORDER by geauxplan_id DESC';
		$geauxplan_items = $wpdb->get_results( $wpdb->prepare( $geauxplan_query, 
                     $search_term, ), ARRAY_A );*/
//
$geauxplan_query = 'select * from ' . $wpdb->get_blog_prefix();
$geauxplan_query .= 'geauxplan where geauxplan_username = %s';

$geauxplan_items = $wpdb->get_results( $wpdb->prepare( $geauxplan_query, 
$current_user->user_login, ), ARRAY_A );

	// Prepare output to be returned to replace shortcode
	$output = '';
	$output .= '<div class="geauxplan-tracker-list"><table>';

	// Check if any bugs were found
	if ( $geauxplan_items ) {
		$output .= '<tr><th style="width: 80px">ID</th>';
		$output .= '<th style="width: 300px">URL / Desc</th>';
		$output .= '<th>Username</th></tr>';

		// Create row in table for each bug
		foreach ( $geauxplan_items as $geauxplan_item ) {
			$output .= '<tr style="background: #FFF">';
			$output .= '<td>' . $geauxplan_item['geauxplan_id'] . '</td>';
		//	$output .= '<td>' . $geauxplan_item['geauxplan_url'] . '</td>';
            $output .= '<td><a href="'.$geauxplan_item['geauxplan_url'] .'" '. '>'. $geauxplan_item['geauxplan_url']. '</a></td>';
			$output .= '<td>' . $geauxplan_item['geauxplan_username'] . '</td></tr>';
		}
	} else {
		// Message displayed if no bugs are found
		$output .= '<tr style="background: #FFF">';
		$output .= '<td colspan=3>No downloads to Display</td>';
	}

	$output .= '</table></div>';

	// Return data prepared to replace shortcode on page/post
	return $output;
}
