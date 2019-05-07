<?php
/**
 * Filename class-plugin.php
 *
 * @package WP_Perf\YouTube_Channel_Sync
 * @author  WP Perf <wpperf@gmail.com>
 * @since   1.0.0
 */

namespace WP_Perf\YouTube_Channel_Sync;

use WP_Perf\YouTube_Channel_Sync\Post_Types\Video;
use WP_Perf\YouTube_Channel_Sync\Taxonomies\Playlist;

/**
 * Class Plugin
 *
 * @package WP_Perf\YouTube_Channel_Sync
 * @since   1.0.0
 */
class Plugin {
	use Singleton;

	const VERSION = '1.0.0';

	/**
	 * Absolute path to plugin folder on server with trailing slash
	 *
	 * @var string
	 */
	public $plugin_path;

	/**
	 * Absolute URL to plugin folder with trailing slash
	 *
	 * @var string
	 */
	public $plugin_url;

	/**
	 * Assets manifest
	 *
	 * @var array
	 */
	public $assets;

	/**
	 * Options
	 *
	 * @var Options
	 */
	public $options;

	/**
	 * Reference to the API Adapter.
	 *
	 * @var API_Adapter
	 */
	public $api;

	/**
	 * Reference to the Cron.
	 *
	 * @var Cron
	 */
	public $cron;

	/**
	 * Reference to the Importer.
	 *
	 * @var Importer
	 */
	public $importer;

	/**
	 * YouTube constructor.
	 */
	private function __construct() {
		$this->plugin_path = plugin_dir_path( __DIR__ );
		$this->plugin_url  = plugin_dir_url( __DIR__ );

		// Init Post Types & Taxonomies.
		new Video();
		new Playlist();

		// Init Options.
		$this->options = new Options();

		// Init Importer.
		$this->importer = new Importer();

		// Init CRON.
		new CRON();

		// Init admin.
		if ( is_admin() ) {
			new Admin();
		}

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
	}

	/**
	 * Get the absolute file path.
	 *
	 * @param string $relative Path fragment to append to absolute file path.
	 *
	 * @return string
	 */
	public function get_plugin_path( $relative = '' ) {
		return $this->plugin_path . $relative;
	}

	/**
	 * Get the absolute url path.
	 *
	 * @param string $relative Path fragment to append to absolute web path.
	 *
	 * @return string
	 */
	public function get_plugin_url( $relative = '' ) {
		return $this->plugin_url . $relative;
	}

	/**
	 * Get the absolute URL for CSS and JS files.
	 * Parses dist/assets.json to retrieve production asset if they exist.
	 *
	 * @param string $path Relative path to asset in dist/ folder, ex: 'scripts/main.js'.
	 *
	 * @return string
	 */
	public function get_assets_url( $path ) {
		if ( ! isset( $this->assets ) ) {
			$manifest = $this->get_plugin_path( 'dist/assets.json' );
			if ( file_exists( $manifest ) ) {
				$this->assets = json_decode( file_get_contents( $manifest ), true ); // phpcs:ignore
			} else {
				$this->assets = [];
			}
		}

		$url = ( isset( $this->assets[ $path ] ) )
			? $this->get_plugin_url( 'dist/' . $this->assets[ $path ] )
			: $this->get_plugin_url( 'dist/' . $path );

		return $url;
	}

	/**
	 * Enqueue styles for frontend.
	 */
	public function enqueue_scripts() {
		if ( ! wpp_youtube()->options->get_plugin_css() ) {
			wp_enqueue_style( 'wpp-yt-main', $this->get_assets_url( 'styles/main.css' ) );
		}
	}

	/**
	 * Plugin Activation Callback
	 *
	 * Enable CRON
	 */
	static function activate() {
		// Activation.
	}

	/**
	 * Plugin Deactivation Callback
	 *
	 * Disable CRON
	 */
	static function deactivate() {
		// Disable CRON
	}

	/**
	 * Plugin Uninstall Callback
	 */
	static function uninstall() {
		// Remove Options
		// Remove Videos?
		// Remove Playlists?
		delete_option( Options::CONFIG_KEY );
		delete_option( Options::CHANNEL_KEY );
	}
}
