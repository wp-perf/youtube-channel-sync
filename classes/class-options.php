<?php
/**
 * Filename class-options.php
 *
 * @package WP_Perf\YouTube_Channel_Sync
 * @author  WP Perf <wpperf@gmail.com>
 * @since   1.0.0
 */

namespace WP_Perf\YouTube_Channel_Sync;

/**
 * Class Options
 *
 * Wrapper for accessing YouTube options
 *
 * @package WP_Perf\YouTube_Channel_Sync
 * @since   1.0.0
 */
class Options {

	const CONFIG_KEY = 'wpp_youtube_channel_sync_options';

	const CONFIG_API_KEY = 'api_key';

	const CONFIG_CHANNEL_ID = 'channel_id';

	const CONFIG_UPDATE_FREQUENCY = 'update_frequency';

	const CONFIG_ORPHANED_VIDEOS = 'orphaned_videos';

	const CONFIG_ORPHANED_PLAYLISTS = 'orphaned_playlists';

	const CONFIG_PLUGIN_CSS = 'plugin_css';


	const CONFIG_DEFAULTS = [
		self::CONFIG_API_KEY            => '',
		self::CONFIG_CHANNEL_ID         => '',
		self::CONFIG_UPDATE_FREQUENCY   => 'oncedaily',
		self::CONFIG_ORPHANED_VIDEOS    => 'keep',
		self::CONFIG_ORPHANED_PLAYLISTS => 'keep',
		self::CONFIG_PLUGIN_CSS         => 'enable',
	];

	/**
	 * Reference to \Google_Service_YouTube_Channel in options table.
	 */
	const CHANNEL_KEY = 'wpp_youtube_channel_sync_channel';

	/**
	 * Stores information about the last sync.
	 */
	const STATUS_LOG_KEY = 'wpp_youtube_channel_sync_log';

	/**
	 * Plugin Config
	 *
	 * @var array
	 */
	private $config;

	/**
	 * Settings constructor.
	 */
	public function __construct() {
		$this->config = get_option( self::CONFIG_KEY );
		add_filter( 'pre_update_option_' . Options::CONFIG_KEY, [ $this, 'pre_update_option_callback' ], 10, 3 );
	}

	/**
	 * Get API Key setting from options.
	 *
	 * @return string
	 */
	public function get_api_key() {
		if ( defined( 'WPP_YOUTUBE_CHANNEL_SYNC_API_KEY' ) ) {
			return WPP_YOUTUBE_CHANNEL_SYNC_API_KEY;
		}

		if ( isset( $this->config[ self::CONFIG_API_KEY ] ) ) {
			return $this->config[ self::CONFIG_API_KEY ];
		}

		return self::CONFIG_DEFAULTS[ self::CONFIG_API_KEY ];
	}

	/**
	 * Get Channel ID setting from options.
	 *
	 * @return string
	 */
	public function get_channel_id() {
		if ( defined( 'WPP_YOUTUBE_CHANNEL_SYNC_CHANNEL_ID' ) ) {
			return WPP_YOUTUBE_CHANNEL_SYNC_CHANNEL_ID;
		}

		if ( isset( $this->config[ self::CONFIG_CHANNEL_ID ] ) ) {
			return $this->config[ self::CONFIG_CHANNEL_ID ];
		}

		return self::CONFIG_DEFAULTS[ self::CONFIG_CHANNEL_ID ];
	}

	/**
	 * Get Update Frequency setting from options.
	 *
	 * @return string
	 */
	public function get_update_frequency() {
		if ( isset( $this->config[ self::CONFIG_UPDATE_FREQUENCY ] ) ) {
			return $this->config[ self::CONFIG_UPDATE_FREQUENCY ];
		}

		return self::CONFIG_DEFAULTS[ self::CONFIG_UPDATE_FREQUENCY ];
	}

	/**
	 * Get Orphaned Videos setting from options.
	 *
	 * @return string
	 */
	public function get_orphaned_videos() {
		if ( isset( $this->config[ self::CONFIG_ORPHANED_VIDEOS ] ) ) {
			return $this->config[ self::CONFIG_ORPHANED_VIDEOS ];
		}

		return self::CONFIG_DEFAULTS[ self::CONFIG_ORPHANED_VIDEOS ];
	}

	/**
	 * Get Orphaned Playlists setting from options.
	 *
	 * @return string
	 */
	public function get_orphaned_playlists() {
		if ( isset( $this->config[ self::CONFIG_ORPHANED_PLAYLISTS ] ) ) {
			return $this->config[ self::CONFIG_ORPHANED_PLAYLISTS ];
		}

		return self::CONFIG_DEFAULTS[ self::CONFIG_ORPHANED_PLAYLISTS ];
	}

	/**
	 * Get Disable Plugin CSS setting from options.
	 *
	 * @return string
	 */
	public function get_plugin_css() {
		if ( isset( $this->config[ self::CONFIG_PLUGIN_CSS ] ) ) {
			return $this->config[ self::CONFIG_PLUGIN_CSS ];
		}

		return self::CONFIG_DEFAULTS[ self::CONFIG_PLUGIN_CSS ];
	}

	/**
	 * Refresh options when saved. Re-sync Channel.
	 *
	 * @param $old_value
	 * @param $value
	 * @param $option
	 *
	 * @return array Values.
	 */
	public function pre_update_option_callback( $value, $option, $old_value ) {
		$this->config = $value;

		wpp_youtube()->importer->import_channel();

		return $value;
	}

	/**
	 * Get YouTube Channel object from options table.
	 *
	 * @return \Google_Service_YouTube_Channel|false
	 */
	public function get_channel() {
		$channel = get_option( self::CHANNEL_KEY, false );

		return $channel;
	}

	/**
	 * @return mixed|void
	 */
	public function get_latest_sync_log() {
		$filepath = get_option( self::STATUS_LOG_KEY );

		if ( ! file_exists( $filepath ) ) {
			return _x( 'No log file found.', '', 'wpp-youtube' );
		}

		$log = file_get_contents( $filepath );

		return $log;
	}

	/**
	 * Set Sync Status.
	 *
	 * @param      $message
	 * @param null $data
	 */
	public function set_latest_sync_log( $filepath ) {
		update_option( self::STATUS_LOG_KEY, $filepath );
	}
}
