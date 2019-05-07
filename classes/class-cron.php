<?php
/**
 * Filename class-cron.php
 *
 * @package demo
 * @author  Peter Toi <peter@petertoi.com>
 */

namespace WP_Perf\YouTube_Channel_Sync;

/**
 * Class Cron
 *
 * Summary
 *
 * @package WP_Perf\YouTube_Channel_Sync
 * @author  Peter Toi <peter@petertoi.com>
 * @version
 */
class Cron {
	const MANUAL_SYNC = 'wpp_youtube_channel_sync_manual';

	const SCHEDULED_SYNC = 'wpp_youtube_channel_sync_scheduled';

	const RECURRENCE = 'hourly';

	/**
	 * Cron constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'maybe_schedule_sync' ] );
		add_action( self::MANUAL_SYNC, [ $this, 'sync' ] );
		add_action( self::SCHEDULED_SYNC, [ $this, 'sync' ] );
	}

	/**
	 * Schedules or un-schedules the sync
	 * Looks at the config settings, as well as the existence of the cron event before schedule/unschedule.
	 *
	 * @return void
	 */
	public function maybe_schedule_sync() {
		$next_scheduled = wp_next_scheduled( self::SCHEDULED_SYNC );

		if ( $this->is_cron_enabled() ) {
			if ( false === $next_scheduled ) {
				$recurrence = $this->validate_recurrence( wpp_youtube()->options->get_update_frequency() );
				wp_schedule_event( current_time( 'timestamp' ), $recurrence, self::SCHEDULED_SYNC );
			}
		} else {
			if ( false !== $next_scheduled ) {
				wp_unschedule_event( $next_scheduled, self::SCHEDULED_SYNC );
			}
		}
	}

	/**
	 * Run the sync.
	 *
	 * @param bool $force Whether to trigger the sync regardless of the `is_cron_enabled` setting.
	 *
	 * @return \WP_Error|array
	 */
	public function sync( $force = false ) {
		if ( ! $force && ! $this->is_cron_enabled() ) {
			$status = new \WP_Error(
				'wpp-youtube-import-error',
				_x( 'Automatic import is disabled.', '', 'wpp-youtube' )
			);

			return $status;
		}

		$status = wpp_youtube()->importer->sync();

		return $status;
	}

	/**
	 * Validate reccurrence schedule. Ensures schedules that aren't defined can't be set.
	 *
	 * @param string $reccurrence The desired recurrence.
	 *
	 * @return string The desired recurrence, or hourly if not found.
	 */
	private function validate_recurrence( $reccurrence ) {
		$valid_recurrences = wp_get_schedules();
		if ( array_key_exists( strtolower( $reccurrence ), $valid_recurrences ) ) {
			return strtolower( $reccurrence );
		} else {
			return 'hourly';
		}
	}

	/**
	 * Helper function for inquiring if the Edgenet Import Cron is enabled or not.
	 *
	 * @return bool
	 */
	private function is_cron_enabled() {
		$enabled = ( 'off' !== wpp_youtube()->options->get_update_frequency() );

		return $enabled;
	}

}