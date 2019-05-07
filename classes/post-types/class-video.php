<?php
/**
 * Filename class-video.php
 *
 * @package WP_Perf\YouTube_Channel_Sync
 * @author  WP Perf <wpperf@gmail.com>
 * @since   1.0.0
 */

namespace WP_Perf\YouTube_Channel_Sync\Post_Types;

use WP_Perf\YouTube_Channel_Sync\Admin;
use WP_Perf\YouTube_Channel_Sync\Template;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Video
 *
 * Summary
 *
 * @package WP_Perf\YouTube_Channel_Sync
 * @since   1.0.0
 */
class Video {

	/**
	 * Post type slug.
	 */
	const POST_TYPE = 'wpp-yt-video';

	/**
	 * Rewrite slug.
	 */
	const REWRITE = 'video';

	const META_VIDEO_ID = '_wpp_yt_video_id';

	const META_VIDEO_ID_ = '_wpp_yt_video_id_';

	const META_PLAYLIST_ITEM = '_wpp_yt_playlist_item';

	const META_VIDEO_URL = '_wpp_yt_video_url';

	const META_VIDEO_OEMBED = '_wpp_yt_video_oembed';

	const SHORTCODE = 'wpp_yt_video';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_post_type' ] );
		add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ] );
		add_action( 'save_post_' . self::POST_TYPE, [ $this, 'save_post' ] );
		add_filter( 'the_content', [ $this, 'add_video_to_post_content' ], 1 );
		add_shortcode( self::SHORTCODE, __CLASS__ . '::render_shortcode' );
	}

	/**
	 * Register post type.
	 */
	public function register_post_type() {
		/**
		 * Document post type labels.
		 */
		$labels = [
			'name'               => esc_html_x( 'Videos', 'Videos Post Type General Name', 'youtube' ),
			'singular_name'      => esc_html_x( 'Video', 'Videos Post Type Singular Name', 'youtube' ),
			'add_new'            => esc_html__( 'Add New', 'youtube' ),
			'add_new_item'       => esc_html__( 'Add New Video', 'youtube' ),
			'edit_item'          => esc_html__( 'Edit Video', 'youtube' ),
			'new_item'           => esc_html__( 'New Video', 'youtube' ),
			'view_item'          => esc_html__( 'View Video', 'youtube' ),
			'search_items'       => esc_html__( 'Search Videos', 'youtube' ),
			'not_found'          => esc_html__( 'No Videos found', 'youtube' ),
			'not_found_in_trash' => esc_html__( 'No Videos found in Trash', 'youtube' ),
			'parent_item_colon'  => '',
			'all_items'          => esc_html__( 'Videos', 'youtube' ),
			'menu_name'          => esc_html__( 'YouTube', 'youtube' ),
		];

		/**
		 * Document post type supports
		 */
		$supports = [ 'title', 'editor', 'thumbnail' ];

		/**
		 * Document post type args
		 */
		$args = [
			'description'         => esc_html__( 'Videos imported from YouTube.', 'youtube' ),
			'labels'              => $labels,
			'public'              => true,
			'publicly_queryable'  => true,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => Admin::PARENT_MENU_SLUG,
			'query_var'           => true,
			'has_archive'         => 'videos',
			'rewrite'             => [
				'slug' => self::REWRITE,
			],
			'hierarchical'        => false,
			'menu_position'       => null,
			'supports'            => $supports,
			'can_export'          => true,
			'show_in_nav_menus'   => true,
			'show_in_admin_bar'   => false,
		];

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Register the Meta Box
	 */
	public function add_meta_box() {
		add_meta_box(
			'youtube-video',
			__( 'Video', 'youtube' ),
			[ $this, 'meta_html' ],
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Meta Box callback.
	 *
	 * @param \WP_Post $post The current post.
	 */
	public function meta_html( $post ) {
		$data = [];

		$data[ self::META_VIDEO_URL ]    = get_post_meta( $post->ID, self::META_VIDEO_URL, true );
		$data[ self::META_VIDEO_OEMBED ] = get_post_meta( $post->ID, self::META_VIDEO_OEMBED, true );

		Template::load( 'admin/video-meta-box', $data );
	}

	/**
	 * Save Video meta.
	 *
	 * @param int $post_id The Post ID.
	 */
	public function save_post( $post_id ) {
		if ( self::POST_TYPE !== get_post_type( $post_id ) ) {
			return;
		}

		$youtube_action = filter_input( INPUT_POST, 'youtube_action', FILTER_SANITIZE_STRING );
		if ( 'save_video' !== $youtube_action ) {
			return;
		}

		$video_url    = filter_input( INPUT_POST, self::META_VIDEO_URL, FILTER_SANITIZE_STRING );
		$video_oembed = wp_oembed_get( $video_url );

		if ( $video_url ) {
			update_post_meta( $post_id, self::META_VIDEO_URL, $video_url );
			update_post_meta( $post_id, self::META_VIDEO_OEMBED, $video_oembed );
		} else {
			delete_post_meta( $post_id, self::META_VIDEO_URL );
			delete_post_meta( $post_id, self::META_VIDEO_OEMBED );
		}
	}

	/**
	 * @param $content
	 *
	 * @return string
	 */
	public function add_video_to_post_content( $content ) {
		global $post;

		if ( self::POST_TYPE !== get_post_type( $post ) ) {
			return $content;
		}

		$shortcode = $this->render_shortcode( [ 'id' => $post->ID ] );

		// TODO: Support prepend and append via options.
		if ( ! empty( $shortcode ) ) {
			$content = $shortcode . $content;
		}

		return $content;
	}

	/**
	 * @param $atts
	 *
	 * @return string
	 */
	static function render_shortcode( $atts ) {
		$atts = shortcode_atts( [
			'id' => false,
		], $atts );

		if ( empty( $atts['id'] ) ) {
			return '';
		}

		$video_title_start = apply_filters( 'wpp_yt_video_title_start', '<h2 class="wpp-yt-video-title">', $atts['id'] );
		$video_title_end   = apply_filters( 'wpp_yt_video_title_end', '</h2>', $atts['id'] );

		$title_html = sprintf(
			'%s%s%s',
			$video_title_start,
			get_the_title( $atts['id'] ),
			$video_title_end
		);

		$video_wrap_start = apply_filters( 'wpp_yt_video_wrap_start', '<article class="wpp-yt-video-wrap">', $atts['id'] );

		$video_start = apply_filters( 'wpp_yt_video_start', '<div class="wpp-yt-video">', $atts['id'] );

		$responsive_embed_start = apply_filters( 'wpp_yt_responsive_embed_start', '<div class="wpp-yt-responsive-embed">', $atts['id'] );
		$responsive_embed_end   = apply_filters( 'wpp_yt_responsive_embed_end', '</div>', $atts['id'] );

		$video_end = apply_filters( 'wpp_yt_video_end', '</div>', $atts['id'] );

		$video_wrap_end = apply_filters( 'wpp_yt_video_wrap_end', '</article>', $atts['id'] );

		$html = sprintf(
			'%s%s%s%s%s%s%s%s',
			$video_wrap_start,
			$video_start,
			$responsive_embed_start,
			get_post_meta( $atts['id'], self::META_VIDEO_OEMBED, true ),
			$responsive_embed_end,
			$video_end,
			$title_html,
			$video_wrap_end
		);

		return apply_filters( 'wpp_yt_video_html', $html, $atts );
	}

	/**
	 * @param $id
	 *
	 * @return string
	 */
	static function youtube_url_from_id( $id ) {
		return 'https://www.youtube.com/watch?v=' . $id;
	}
}



