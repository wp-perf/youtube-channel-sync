<?php
/**
 * Filename class-admin.php
 *
 * @package WP_Perf\YouTube_Channel_Sync
 * @author  WP Perf <wpperf@gmail.com>
 * @since   1.0.0
 */

namespace WP_Perf\YouTube_Channel_Sync;

/**
 * Class Admin
 *
 * Summary
 *
 * @package WP_Perf\YouTube_Channel_Sync
 * @since   1.0.0
 */
class Admin {

	const PARENT_MENU_SLUG = 'wpp_youtube_channel_sync';

	const SETTINGS_PAGE_SLUG = 'wpp_youtube_channel_sync_settings';

	const SYNC_PAGE_SLUG = 'wpp_youtube_channel_sync_status';

	const SETTINGS_OPTION_GROUP = 'wpp_youtube';

	const SETTINGS_SECTION_GENERAL = 'wpp_youtube_config_general';

	const SETTINGS_SECTION_SYNC = 'wpp_youtube_config_sync';

	const SETTINGS_SECTION_DISPLAY = 'wpp_youtube_config_display';

	const MANUAL_SYNC_ACTION = 'wpp_youtube_channel_sync_manual_sync';

	/**
	 * Admin constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'manual_sync_callback' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_menu', [ $this, 'admin_menu_callback' ] );
		add_action( 'admin_menu', [ $this, 'settings_submenu_callback' ] );
		add_action( 'admin_menu', [ $this, 'sync_submenu_callback' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts_callback' ] );
		add_action( 'load-youtube_page_' . self::SETTINGS_PAGE_SLUG, [ $this, 'set_global_notifications' ] );
	}

	/**
	 * Set up a custom Admin Menu for our plugin.
	 */
	public function admin_menu_callback() {
		add_menu_page(
			_x( 'YouTube Channel Sync by WP Perf', '', 'wpp-youtube' ),
			_x( 'YouTube Sync', '', 'wpp-youtube' ),
			'manage_options',
			self::PARENT_MENU_SLUG,
			'',
			'dashicons-video-alt3',
			25
		);
	}

	/**
	 * Register Settings Page.
	 */
	public function settings_submenu_callback() {
		add_submenu_page(
			self::PARENT_MENU_SLUG,
			_x( 'YouTube Channel Sync Settings', '', 'wpp-youtube' ),
			_x( 'Settings', '', 'wpp-youtube' ),
			'manage_options',
			self::SETTINGS_PAGE_SLUG,
			[ $this, 'settings_page_callback' ]
		);
	}

	/**
	 * Settings Page Callback
	 */
	public function settings_page_callback() {
		Template::load( 'admin/page-settings' );
	}

	/**
	 * Register Sync Page.
	 */
	public function sync_submenu_callback() {
		add_submenu_page(
			self::PARENT_MENU_SLUG,
			_x( 'YouTube Channel Sync Status', '', 'wpp-youtube' ),
			_x( 'Sync', '', 'wpp-youtube' ),
			'manage_options',
			self::SYNC_PAGE_SLUG,
			[ $this, 'sync_page_callback' ]
		);
	}

	/**
	 * Sync Page Callback
	 */
	public function sync_page_callback() {
		Template::load( 'admin/page-sync' );
	}

	/**
	 * Register Settings
	 */
	public function register_settings() {
		register_setting(
			self::SETTINGS_OPTION_GROUP,
			Options::CONFIG_KEY,
			[
				'type'              => 'string',
				'description'       => _x( 'YouTube Channel Sync options.', '', 'wpp-youtube' ),
				'sanitize_callback' => [ $this, 'sanitize_settings_callback' ],
				'show_in_rest'      => false,
				'default'           => Options::CONFIG_DEFAULTS,
			]
		);

		/**
		 * General Settings
		 */
		add_settings_section(
			self::SETTINGS_SECTION_GENERAL,
			_x( 'General', '', 'wpp-youtube' ),
			[ $this, 'general_section_callback' ],
			self::SETTINGS_PAGE_SLUG
		);

		add_settings_field(
			Options::CONFIG_API_KEY,
			Template::render_label( _x( 'API Key', '', 'wpp-youtube' ), 'wpp-youtube-api-key' ),
			[ $this, 'setting_api_key_callback' ],
			self::SETTINGS_PAGE_SLUG,
			self::SETTINGS_SECTION_GENERAL
		);

		add_settings_field(
			Options::CONFIG_CHANNEL_ID,
			Template::render_label( _x( 'Channel ID', '', 'wpp-youtube' ), 'wpp-youtube-channel-id' ),
			[ $this, 'setting_channel_id_callback' ],
			self::SETTINGS_PAGE_SLUG,
			self::SETTINGS_SECTION_GENERAL
		);

		add_settings_field(
			'channel-preview',
			Template::render_label( _x( 'Channel Preview', '', 'wpp-youtube' ), 'wpp-youtube-channel-preview' ),
			[ $this, 'setting_channel_preview_callback' ],
			self::SETTINGS_PAGE_SLUG,
			self::SETTINGS_SECTION_GENERAL
		);

		if ( ! wpp_youtube()->options->get_channel() ) {
			return;
		}

		/**
		 * Sync Settings
		 */
		add_settings_section(
			self::SETTINGS_SECTION_SYNC,
			_x( 'Sync', '', 'wpp-youtube' ),
			[ $this, 'sync_section_callback' ],
			self::SETTINGS_PAGE_SLUG
		);

		add_settings_field(
			'sync-status',
			Template::render_label( _x( 'Sync Status', '', 'wpp-youtube' ), 'wpp-youtube-sync-status' ),
			[ $this, 'setting_sync_status_callback' ],
			self::SETTINGS_PAGE_SLUG,
			self::SETTINGS_SECTION_SYNC
		);

		add_settings_field(
			Options::CONFIG_UPDATE_FREQUENCY,
			Template::render_label( _x( 'Update Frequency', '', 'wpp-youtube' ), 'wpp-youtube-update-frequency' ),
			[ $this, 'setting_update_frequency_callback' ],
			self::SETTINGS_PAGE_SLUG,
			self::SETTINGS_SECTION_SYNC
		);

		add_settings_field(
			Options::CONFIG_ORPHANED_PLAYLISTS,
			Template::render_label( _x( 'Orphaned Playlists', '', 'wpp-youtube' ), 'wpp-youtube-orphaned-playlists' ),
			[ $this, 'setting_orphaned_playlists_callback' ],
			self::SETTINGS_PAGE_SLUG,
			self::SETTINGS_SECTION_SYNC
		);

		add_settings_field(
			Options::CONFIG_ORPHANED_VIDEOS,
			Template::render_label( _x( 'Orphaned Videos', '', 'wpp-youtube' ), 'wpp-youtube-orphaned-videos' ),
			[ $this, 'setting_orphaned_videos_callback' ],
			self::SETTINGS_PAGE_SLUG,
			self::SETTINGS_SECTION_SYNC
		);

		/**
		 * Display Settings
		 */
		add_settings_section(
			self::SETTINGS_SECTION_DISPLAY,
			_x( 'Display', '', 'wpp-youtube' ),
			[ $this, 'display_section_callback' ],
			self::SETTINGS_PAGE_SLUG
		);

		add_settings_field(
			Options::CONFIG_API_KEY,
			Template::render_label( _x( 'Plugin CSS', '', 'wpp-youtube' ), 'wpp-youtube-plugin-css' ),
			[ $this, 'setting_plugin_css_callback' ],
			self::SETTINGS_PAGE_SLUG,
			self::SETTINGS_SECTION_DISPLAY
		);
	}

	/**
	 * Section: General
	 *
	 * Output content between section title and fields.
	 *
	 * @param array $args Section args.
	 */
	public function general_section_callback( $args ) {
		echo '';
	}

	/**
	 * Field: API Key
	 *
	 * Renders the field.
	 */
	public function setting_api_key_callback() {
		echo Template::render_input(
			'text',
			Options::CONFIG_KEY . '[' . Options::CONFIG_API_KEY . ']',
			'wpp-youtube-api-key',
			wpp_youtube()->options->get_api_key(),
			[ 'class' => 'regular-text' ]
		);
	}

	/**
	 * Field: Channel ID
	 *
	 * Renders the field.
	 */
	public function setting_channel_id_callback() {
		echo Template::render_input(
			'text',
			Options::CONFIG_KEY . '[' . Options::CONFIG_CHANNEL_ID . ']',
			'wpp-youtube-channel-id',
			wpp_youtube()->options->get_channel_id(),
			[ 'class' => 'regular-text' ]
		);
	}

	/**
	 * Field: Channel Preview
	 */
	public function setting_channel_preview_callback() {
		Template::load( 'admin/field-channel-preview' );
	}

	/**
	 * Section: Sync
	 *
	 * Output content between section title and fields.
	 *
	 * @param array $args Section args.
	 */
	public function sync_section_callback( $args ) {
		echo '';
	}

	/**
	 * Field: Sync Status
	 */
	public function setting_sync_status_callback() {
		Template::load( 'admin/field-sync-status' );
	}

	/**
	 * Field: Update Frequency
	 *
	 * Renders the field.
	 */
	public function setting_update_frequency_callback() {
		echo Template::render_select(
			Options::CONFIG_KEY . '[' . Options::CONFIG_UPDATE_FREQUENCY . ']',
			'wpp-youtube-update-frequency',
			[
				[ 'value' => 'off', 'label' => _x( 'Off', '', 'wpp-youtube' ) ],
				[ 'value' => 'hourly', 'label' => _x( 'Hourly', '', 'wpp-youtube' ) ],
				[ 'value' => 'twicedaily', 'label' => _x( 'Twice Daily', '', 'wpp-youtube' ) ],
				[ 'value' => 'daily', 'label' => _x( 'Once Daily', '', 'wpp-youtube' ) ],
			],
			wpp_youtube()->options->get_update_frequency(),
			[]
		);
	}

	/**
	 * Field: Orphaned Playlists
	 *
	 * Renders the field.
	 */
	public function setting_orphaned_playlists_callback() {
		echo Template::render_select(
			Options::CONFIG_KEY . '[' . Options::CONFIG_ORPHANED_PLAYLISTS . ']',
			'wpp-youtube-orphaned-playlists',
			[
				[ 'value' => 'delete', 'label' => _x( 'Delete Permanently', '', 'wpp-youtube' ) ],
				[ 'value' => 'keep', 'label' => _x( 'Keep', '', 'wpp-youtube' ) ],
			],
			wpp_youtube()->options->get_orphaned_playlists(),
			[]
		);
	}

	/**
	 * Field: Orphaned Videos
	 *
	 * Renders the field.
	 */
	public function setting_orphaned_videos_callback() {
		echo Template::render_select(
			Options::CONFIG_KEY . '[' . Options::CONFIG_ORPHANED_VIDEOS . ']',
			'wpp-youtube-orphaned-videos',
			[
				[ 'value' => 'delete', 'label' => _x( 'Delete Permanently', '', 'wpp-youtube' ) ],
				[ 'value' => 'trash', 'label' => _x( 'Move to Trash', '', 'wpp-youtube' ) ],
				[ 'value' => 'keep', 'label' => _x( 'Keep', '', 'wpp-youtube' ) ],
			],
			wpp_youtube()->options->get_orphaned_videos(),
			[]
		);
	}

	/**
	 * Section: Display
	 *
	 * Output content between section title and fields.
	 *
	 * @param array $args Section args.
	 */
	public function display_section_callback( $args ) {
		echo '';
	}

	/**
	 * Field: API Key
	 *
	 * Renders the field.
	 */
	public function setting_plugin_css_callback() {
		echo Template::render_select(
			Options::CONFIG_KEY . '[' . Options::CONFIG_PLUGIN_CSS . ']',
			'wpp-youtube-plugin-css',
			[
				[ 'value' => 'enable', 'label' => _x( 'Enable', '', 'wpp-youtube' ) ],
				[ 'value' => 'disable', 'label' => _x( 'Disable', '', 'wpp-youtube' ) ],
			],
			wpp_youtube()->options->get_plugin_css(),
			[]
		);
	}

	/**
	 * Sanitize settings before save.
	 * Will also check YouTube API for Channel Info if provided.
	 *
	 * @param $settings
	 *
	 * @return mixed
	 */
	public function sanitize_settings_callback( $settings ) {

		$regex = '/[^\w\d_-]/';

		$sanitized_settings = [];

		foreach ( $settings as $key => $value ) {
			switch ( $key ) {
				case Options::CONFIG_API_KEY:
				case Options::CONFIG_CHANNEL_ID:
					$sanitized_value = preg_replace( $regex, '', $value );
					break;
				case Options::CONFIG_UPDATE_FREQUENCY:
					$sanitized_value = $this->sanitize_options(
						$value,
						[ 'off', 'hourly', 'twicedaily', 'oncedaily' ],
						Options::CONFIG_DEFAULTS[ Options::CONFIG_UPDATE_FREQUENCY ]
					);
					break;
				case Options::CONFIG_ORPHANED_PLAYLISTS:
					$sanitized_value = $this->sanitize_options(
						$value,
						[ 'delete', 'keep' ],
						Options::CONFIG_DEFAULTS[ Options::CONFIG_ORPHANED_PLAYLISTS ]
					);
					break;
				case Options::CONFIG_ORPHANED_VIDEOS:
					$sanitized_value = $this->sanitize_options(
						$value,
						[ 'delete', 'trash', 'keep' ],
						Options::CONFIG_DEFAULTS[ Options::CONFIG_ORPHANED_VIDEOS ]
					);
					break;
				case Options::CONFIG_PLUGIN_CSS:
					$sanitized_value = $this->sanitize_options(
						$value,
						[ 'enable', 'disable' ],
						Options::CONFIG_DEFAULTS[ Options::CONFIG_PLUGIN_CSS ]
					);
					break;
				default:
					break;
			}

			if ( $sanitized_value ) {
				$sanitized_settings[ $key ] = $sanitized_value;
			}

		}

		return $sanitized_settings;
	}

	/**
	 * Sanitize value by ensuring it matches a set of provided options.
	 *
	 * @param $value
	 * @param $allowed
	 * @param $fallback
	 *
	 * @return mixed
	 */
	public function sanitize_options( $value, $allowed, $fallback ) {
		return ( false === array_search( $value, $allowed ) )
			? $fallback
			: $value;
	}

	/**
	 * Enqueue scripts.
	 *
	 * @param string $hook_suffix Page hook.
	 */
	public function admin_enqueue_scripts_callback( $hook_suffix ) {
		// TODO: Only load scripts on YouTube Channel Sync resource pages.
		wp_enqueue_style( 'wpp-youtube-admin', wpp_youtube()->get_assets_url( 'styles/admin.css' ), [], Plugin::VERSION );
		wp_enqueue_script( 'wpp-youtube-admin', wpp_youtube()->get_assets_url( 'scripts/admin.js' ), [ 'jquery', 'jquery-ui-tabs' ], Plugin::VERSION, true );
	}

	/**
	 * Set Global Notifications.
	 */
	public function set_global_notifications() {
		if ( empty( wpp_youtube()->options->get_api_key() ) || empty( wpp_youtube()->options->get_channel_id() ) ) {
			add_settings_error(
				self::SETTINGS_OPTION_GROUP,
				'wpp-youtube-error-missing-api-key-channel-id',
				_x( 'A Google API Key and YouTube Channel ID are required before you can sync.', '', 'wpp-youtube' ),
				'error'
			);
		}
	}

	/**
	 * Trigger Manual Sync when the user requests one from WP Admin.
	 *
	 * @return bool
	 */
	public function manual_sync_callback() {
		// Check if action is set, bail early if not.
		$action = filter_input( INPUT_POST, 'action', FILTER_SANITIZE_STRING );
		if ( self::MANUAL_SYNC_ACTION !== $action ) {
			return false;
		}

		// Verify nonce
		$nonce = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
		if ( ! wp_verify_nonce( $nonce, self::MANUAL_SYNC_ACTION ) ) {
			wp_die( new \WP_Error( 'wpp-youtube-invalid-nonce', _x( 'Invalid Nonce.', '', 'wpp-youtube' ) ) );
		};

		// Trigger Manual Sync!
		wp_schedule_single_event( time(), Cron::MANUAL_SYNC, [ 'force' => true ] );

		// Redirect to Sync Status.
		$status_url = add_query_arg(
			[ 'page' => self::SYNC_PAGE_SLUG, ],
			get_admin_url( null, 'admin.php' )
		);

		wp_safe_redirect( $status_url );

		return true;
	}
}
