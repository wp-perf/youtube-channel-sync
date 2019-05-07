<?php
/**
 * Filename class-playlist.php
 *
 * @package WP_Perf\YouTube_Channel_Sync
 * @author  WP Perf <wpperf@gmail.com>
 * @since   1.0.0
 */

namespace WP_Perf\YouTube_Channel_Sync\Taxonomies;

use WP_Perf\YouTube_Channel_Sync\Admin;
use WP_Perf\YouTube_Channel_Sync\Post_Types\Video;

/**
 * Class Playlist
 *
 * Summary
 *
 * @package WP_Perf\YouTube_Channel_Sync
 * @since   1.0.0
 */
class Playlist {

	/**
	 * Taxonomy slug.
	 */
	const TAXONOMY = 'wpp-yt-playlist';

	/**
	 * Rewrite slug.
	 */
	const REWRITE = 'playlist';

	const META_PLAYLIST_ID = '_wpp_yt_playlist_id';

	const META_PLAYLIST_ID_ = '_wpp_yt_playlist_id_';

	const META_PLAYLIST = '_wpp_yt_playlist';

	const UPLOADS_SLUG = 'uploads';

	const SHORTCODE = 'wpp_yt_playlist';

	const SHORTCODE_DISPLAY_EMBED = 'embed';

	const SHORTCODE_DISPLAY_GRID = 'grid';

	/**
	 * Brand constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_taxonomy' ] );
		add_action( 'admin_menu', [ $this, 'custom_admin_menu' ] );
		add_action( 'parent_file', [ $this, 'custom_parent_file' ] );
		add_filter( 'manage_' . Video::POST_TYPE . '_posts_columns', [ $this, 'filter_posts_columns' ] );
		add_action( 'manage_' . Video::POST_TYPE . '_posts_custom_column', [ $this, 'column_content' ], 9999, 2 );
		add_filter( 'get_the_archive_description', [ $this, 'append_player_to_description' ] );
		add_shortcode( self::SHORTCODE, __CLASS__ . '::render_shortcode' );
	}

	/**
	 * Register Brand and link to Product.
	 */
	public function register_taxonomy() {
		$labels = [
			'name'                       => _x( 'Playlists', 'taxonomy general name', 'youtube' ),
			'singular_name'              => _x( 'Playlist', 'taxonomy singular name', 'youtube' ),
			'search_items'               => __( 'Search Playlists', 'youtube' ),
			'popular_items'              => __( 'Popular Playlists', 'youtube' ),
			'all_items'                  => __( 'All Playlists', 'youtube' ),
			'parent_item'                => null,
			'parent_item_colon'          => null,
			'edit_item'                  => __( 'Edit Playlist', 'youtube' ),
			'update_item'                => __( 'Update Playlist', 'youtube' ),
			'add_new_item'               => __( 'Add New Playlist', 'youtube' ),
			'new_item_name'              => __( 'New Playlist Name', 'youtube' ),
			'separate_items_with_commas' => __( 'Separate playlists with commas', 'youtube' ),
			'add_or_remove_items'        => __( 'Add or remove playlists', 'youtube' ),
			'choose_from_most_used'      => __( 'Choose from the most used playlists', 'youtube' ),
			'not_found'                  => __( 'No playlists found.', 'youtube' ),
			'menu_name'                  => __( 'Playlists', 'youtube' ),
		];

		$args = [
			'rewrite'      => [ 'slug' => self::REWRITE ],
			'hierarchical' => false,
			'labels'       => $labels,
		];

		register_taxonomy(
			self::TAXONOMY,
			Video::POST_TYPE,
			$args
		);
	}

	/**
	 * Move Playlists under our custom Admin Menu.
	 */
	public function custom_admin_menu() {
		add_submenu_page(
			Admin::PARENT_MENU_SLUG,
			_x( 'Playlists', '', 'wpp-youtube' ),
			_x( 'Playlists', '', 'wpp-youtube' ),
			'manage_options',
			'edit-tags.php?taxonomy=' . self::TAXONOMY,
			null
		);
	}

	/**
	 * Highlight the custom Admin Menu when editing Playlists.
	 *
	 * @return string
	 */
	public function custom_parent_file( $parent_file ) {
		if ( get_current_screen()->taxonomy === self::TAXONOMY ) {
			$parent_file = Admin::PARENT_MENU_SLUG;
		}

		return $parent_file;
	}

	/**
	 * @param $columns
	 *
	 * @return mixed
	 */
	public function filter_posts_columns( $columns ) {
		$columns['playlists'] = _x( 'Playlists', '', 'wpp-youtube' );

		// Move date to end.
		$date = $columns['date'];
		unset( $columns['date'] );
		$columns['date'] = $date;

		return $columns;
	}

	/**
	 * @param $column
	 * @param $post_id
	 */
	function column_content( $column, $post_id ) {
		if ( 'playlists' === $column ) {
			$terms = wp_get_post_terms( $post_id, self::TAXONOMY );
			$links = [];
			foreach ( $terms as $term ) {
				$links[ $term->name ] = edit_term_link( $term->name, '', '', $term, false );
			}

			ksort( $links );

			echo implode( '<br>', $links );
		}
	}

	/**
	 * @param $description
	 *
	 * @return string
	 */
	public function append_player_to_description( $description ) {

		if ( is_archive() && is_tax( self::TAXONOMY ) ) {

			/**
			 * Playlist Term
			 *
			 * @var \WP_Term $playlist
			 */
			$term = get_queried_object();

			$player = $this->render_shortcode( [ 'id' => $term->term_id ] );

			$description .= $player;
		}

		return $description;
	}

	/**
	 * @param $atts
	 *
	 * @return string
	 */
	static function render_shortcode( $atts ) {
		$atts = shortcode_atts( [
			'id'         => false,
			'display'    => self::SHORTCODE_DISPLAY_EMBED,
			'cols'       => 3,
			'show_title' => true,
		], $atts );

		if ( empty( $atts['id'] ) ) {
			return '';
		}

		switch ( $atts['display'] ) {
			case self::SHORTCODE_DISPLAY_GRID:
				$html = self::get_shortcode_grid_html( $atts );
				break;
			case self::SHORTCODE_DISPLAY_EMBED :
			default:
				$html = self::get_shortcode_embed_html( $atts );
				break;
		}

		return apply_filters( 'wpp_yt_playlist_html', $html, $atts );
	}

	/**
	 * @param $id
	 *
	 * @return string
	 */
	static function get_shortcode_grid_html( $atts ) {
		$args = [
			'post_type' => Video::POST_TYPE,
			'tax_query' => [
				[
					'taxonomy' => self::TAXONOMY,
					'field'    => 'id',
					'terms'    => $atts['id'],
				],
			],
			'fields'    => 'ids',
		];

		$query = new \WP_Query( $args );

		if ( ! $query->have_posts() ) {
			return '';
		}

		$formatted_video_items = array_map( function ( $video_id ) use ( $atts ) {
			$class = sprintf( 'wpp-yt-grid-col-%d', absint( $atts['cols'] ) );

			$videos_item_wrap_start = apply_filters( 'wpp_yt_playlist_videos_item_wrap_start', '<div class="' . $class . '">', $atts['id'], $video_id );
			$videos_item_wrap_end   = apply_filters( 'wpp_yt_playlist_videos_item_wrap_end', '</div>', $atts['id'], $video_id );

			return sprintf( '%s%s%s',
				$videos_item_wrap_start,
				Video::render_shortcode( [ 'id' => $video_id ] ),
				$videos_item_wrap_end
			);
		}, $query->posts );

		$videos_grid_wrap_start = apply_filters( 'wpp_yt_playlist_videos_grid_wrap_start', '<div class="wpp-yt-playlist-videos">', $atts['id'] );
		$videos_grid_wrap_end   = apply_filters( 'wpp_yt_playlist_videos_grid_wrap_end', '</div>', $atts['id'] );

		$html = sprintf(
			'%s%s%s',
			$videos_grid_wrap_start,
			implode( PHP_EOL, $formatted_video_items ),
			$videos_grid_wrap_end
		);

		return apply_filters( 'wpp_yt_playlist_grid_html', $html, $atts );
	}

	/**
	 * @param $id
	 *
	 * @return string
	 */
	static function get_shortcode_embed_html( $atts ) {
		$playlist_wrap_start = apply_filters( 'wpp_yt_playlist_wrap_start', '<div class="wpp-yt-playlist-videos wpp-yt-responsive-embed">', $atts['id'] );
		$playlist_wrap_end   = apply_filters( 'wpp_yt_playlist_wrap_end', '</div>', $atts['id'] );

		$playlist = get_term_meta( $atts['id'], self::META_PLAYLIST, true );

		$player = str_replace( 'http://', '//', $playlist->getPlayer()->getEmbedHtml() );

		$html = sprintf(
			'%s%s%s',
			$playlist_wrap_start,
			$player,
			$playlist_wrap_end
		);

		return apply_filters( 'wpp_yt_playlist_embed_html', $html, $atts );
	}


}