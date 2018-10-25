<?php
/*
Plugin Name: SGC Event Plugin
Description: Support for a simple "Events" post type for the SGC Bellingham Theme
Author: Michael Savchuk
License: MIT
*/

// --- Misc: --- //

function sgc_event_validate_datetime($datestring) {
	$datetime = DateTime::createFromFormat('Y-m-d H:i:s', $datestring);

	if ($datetime !== false) {
		$errors = DateTime::getLastErrors();

		if (empty($errors['warning_count'])) {
			return true;
		}
	}
	
	return false;
}

function sgc_event_get_post_param($name, $default = '') {
	return isset($_POST[$name]) ? $_POST[$name] : $default;
}

function sgc_event_update_meta($post_id, $meta_key, $meta_value) {
	$meta_current_value = get_post_meta($post_id, $meta_key, true);
	
	if ($meta_value == '' && $meta_current_value != '') {
		delete_post_meta($post_id, $meta_key, $meta_current_value);
	} else if ($meta_value != $meta_current_value) {
		update_post_meta($post_id, $meta_key, $meta_value);
	}
}

// --- Post Type: --- //

function sgc_event_init() {
	register_post_type('event', array(
			'labels' => array(
				'name' => __('Events'),
				'singular_name' => __('Event')
			),
			'public' => true,
			'has_archive' => true,
			'menu_position' => 25,
			'menu_icon' => 'dashicons-calendar-alt',
			'supports' => array('title', 'editor')
		)
	);
}

// --- Meta Boxes: --- //

function sgc_event_metaboxes() {
	add_meta_box(
		'sgc_event_info',
		'Event Information',
		'simple_band_event_info_html',
		'event',
		'advanced',
		'high'
	);
}

function simple_band_event_info_html($post) {
	$datetime_start_meta = get_post_meta($post->ID, 'sgc_event_start_datetime', true);
	$datetime_end_meta   = get_post_meta($post->ID, 'sgc_event_end_datetime', true);

	$include_start_time_meta = get_post_meta($post->ID, 'sgc_event_include_start_time', true);
	$include_end_time_meta   = get_post_meta($post->ID, 'sgc_event_include_end_time', true);
	
	$datetime_start = DateTime::createFromFormat('Y-m-d H:i:s', $datetime_start_meta);
	$datetime_end   = DateTime::createFromFormat('Y-m-d H:i:s', $datetime_end_meta);

	$time_start = $include_start_time_meta === 'true' ? $datetime_start->format('H:i:s') : '';
	$time_end   = $include_end_time_meta === 'true' ? $datetime_end->format('H:i:s') : '';

	$include_start_time_checked = $include_start_time_meta === 'true' ? 'checked' : '';
	$include_end_time_checked   = $include_end_time_meta === 'true' ? 'checked' : '';

	?>
		<?php wp_nonce_field(basename(__FILE__), 'sgc-event-nonce') ?>

		<label for="sgc-event-date">Date</label><br>
		<input
			type="text"
			id="sgc-event-date"
			name="sgc-event-date"
			class="sgc-event-input sgc-event-date-input"
			value="<?php echo esc_attr($datetime_start->format('Y-m-d')) ?>"
		>

		<br>

		<label for="sgc-event-include-start-time" class="sgc-checkbox-label">
			<input <?php echo $include_start_time_checked; ?> type="checkbox" id="sgc-event-include-start-time" name="sgc-event-include-start-time" value="true">
			Include start time
		</label><br>
		
		<input
			type="text"
			id="sgc-event-start-time"
			name="sgc-event-start-time"
			class="sgc-event-input sgc-event-time-input"
			value="<?php echo esc_attr($time_start) ?>"
			aria-label="Start time"
		>

		<br>

		<label for="sgc-event-include-end-time" class="sgc-checkbox-label">
			<input <?php echo $include_end_time_checked; ?> type="checkbox" id="sgc-event-include-end-time" name="sgc-event-include-end-time" value="true">
			Include end time
		</label><br>
		
		<input
			type="text"
			id="sgc-event-end-time"
			name="sgc-event-end-time"
			class="sgc-event-input sgc-event-time-input"
			value="<?php echo esc_attr($time_end) ?>"
			aria-label="End time"
		>
	<?php
}

function sgc_event_save_meta($post_id, $post) {
	if (
		!isset($_POST['sgc-event-nonce'])
		|| !wp_verify_nonce($_POST['sgc-event-nonce'], basename(__FILE__))
	) return $post_id;

	$post_type = get_post_type_object($post->post_type);
	if (!current_user_can($post_type->cap->edit_post, $post_id)) return $post_id;

	// Get post params
	$date_meta       = sgc_event_get_post_param('sgc-event-date', '');
	$start_time_meta = sgc_event_get_post_param('sgc-event-start-time', '');
	$end_time_meta   = sgc_event_get_post_param('sgc-event-end-time', '');

	$include_start_time_meta   = sgc_event_get_post_param('sgc-event-include-start-time', 'false');
	$include_end_time_meta     = sgc_event_get_post_param('sgc-event-include-end-time', 'false');

	// Set start datetime
	if ($include_start_time_meta !== 'true') $start_time_meta = '00:00:00';
	$datetime_start_meta = $date_meta . ' ' . $start_time_meta;

	// Set end datetime
	if ($include_end_time_meta !== 'true') $end_time_meta = '00:00:00';
	$datetime_end_meta = $date_meta . ' ' . $end_time_meta;

	// Validation
	if (!sgc_event_validate_datetime($datetime_start_meta)) $datetime_start_meta = date('Y-m-d 00:00:00');
	if (!sgc_event_validate_datetime($datetime_end_meta)) $datetime_end_meta = '';

	sgc_event_update_meta($post_id, 'sgc_event_start_datetime', $datetime_start_meta);
	sgc_event_update_meta($post_id, 'sgc_event_end_datetime', $datetime_end_meta);
	sgc_event_update_meta($post_id, 'sgc_event_include_start_time', $include_start_time_meta);
	sgc_event_update_meta($post_id, 'sgc_event_include_end_time', $include_end_time_meta);
}

// --- Hooks: --- //

function sgc_event_admin_scripts() {
	wp_enqueue_script('sgc-event-script', plugin_dir_url(__FILE__) . '/dist/main.js', array(), false, true);
}

function sgc_event_metaboxes_init() {
	add_action('add_meta_boxes', 'sgc_event_metaboxes');
	add_action('admin_enqueue_scripts', 'sgc_event_admin_scripts');
	add_action('save_post', 'sgc_event_save_meta', 10, 2);
}

add_action('init', 'sgc_event_init');
add_action('load-post.php', 'sgc_event_metaboxes_init');
add_action('load-post-new.php', 'sgc_event_metaboxes_init');