<?php
/**
 * Filename settings.php
 *
 * @package WP_Perf\YouTube_Channel_Sync
 * @since   1.0.0
 */

use WP_Perf\YouTube_Channel_Sync\Admin;

?>
<div id="wpp-youtube-channel-sync-settings" class="wrap">
	<h1><?php echo esc_html_x( 'YouTube Channel Sync - Settings', '', 'wpp-youtube' ); ?></h1>
	<form method="POST" action="options.php">
		<?php
		settings_errors( Admin::SETTINGS_OPTION_GROUP );
		settings_fields( Admin::SETTINGS_OPTION_GROUP );
		do_settings_sections( Admin::SETTINGS_PAGE_SLUG );
		submit_button( _x( 'Save Settings', '', 'wpp-youtube' ) );
		?>
	</form>
</div>