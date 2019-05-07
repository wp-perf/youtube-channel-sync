<?php
/**
 * Filename page-sync.php
 *
 * @package WP_Perf\YouTube_Channel_Sync
 * @since   1.0.0
 */

use WP_Perf\YouTube_Channel_Sync\Admin;
use WP_Perf\YouTube_Channel_Sync\Importer;

?>
<div id="wpp-youtube-channel-sync-status" class="wrap">
	<h1><?php echo esc_html_x( 'YouTube Channel Sync - Status', '', 'wpp-youtube' ); ?></h1>
	<form method="POST">
		<?php $mutex = get_option( Importer::SYNC_MUTEX ); ?>
		<?php if ( $mutex ) : ?>
			<h2><?php _ex( 'Sync in progress', '', 'wpp-youtube' ); ?></h2>
			<p><?php _ex( 'Refresh for updates', '', 'wpp-youtube' ); ?></p>
		<?php else : ?>
			<h2><?php _ex( 'Latest sync', '', 'wpp-youtube' ); ?></h2>
		<?php endif; ?>
		<input type="hidden" name="action" value="<?php echo Admin::MANUAL_SYNC_ACTION; ?>" />
		<?php wp_nonce_field( Admin::MANUAL_SYNC_ACTION ); ?>
		<label for="wpp_youtube_latest_sync_log"></label>
		<textarea name="wpp_youtube_latest_sync_log" id="wpp_youtube_latest_sync_log" cols="70" rows="15" disabled readonly><?php echo wpp_youtube()->options->get_latest_sync_log(); ?></textarea>
		<?php if ( ! $mutex ) : ?>
			<?php submit_button( _x( 'Sync Now', '', 'wpp-youtube' ), 'primary', Admin::MANUAL_SYNC_ACTION ); ?>
		<?php endif; ?>
	</form>
</div>