<?php
/**
 * Filename class-importer.php
 *
 * @package WP_Perf\YouTube_Channel_Sync
 * @author  WP Perf <wpperf@gmail.com>
 * @since   1.0.0
 */

namespace WP_Perf\YouTube_Channel_Sync;

use Monolog\Formatter\LineFormatter;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use WP_Perf\YouTube_Channel_Sync\Post_Types\Video;
use WP_Perf\YouTube_Channel_Sync\Taxonomies\Playlist;

/**
 * Class Importer
 *
 * Summary
 *
 * @package WP_Perf\YouTube_Channel_Sync
 * @since   1.0.0
 */
class Importer {

	/**
	 * Options key for storing sync mutex.
	 */
	const SYNC_MUTEX = 'wpp_youtube_channel_sync_mutex';

	/**
	 * Number of seconds to wait before clearing the mutex.
	 *
	 * Set to 30 minutes.
	 */
	const SYNC_TIMEOUT = MINUTE_IN_SECONDS * 30;

	/**
	 * Importer constructor.
	 */
	public function __construct() {
	}

	/**
	 * Perform a full Channel/Playlist/Video sync.
	 *
	 * @return array|false
	 */
	public function sync() {

		$lock_timestamp = get_option( self::SYNC_MUTEX );

		if ( $lock_timestamp && time() <= $lock_timestamp + self::SYNC_TIMEOUT ) {
			// Sync in progress
			return false;
		} elseif ( $lock_timestamp && time() > $lock_timestamp + self::SYNC_TIMEOUT ) {
			// Too much time has passed, clear mutex and allow another sync to start.
			delete_option( self::SYNC_MUTEX );
		}

		$log_filepath = trailingslashit( $this->get_log_path() ) . date( 'Y-m-d-Gis' ) . '.log';

		wpp_youtube()->options->set_latest_sync_log( $log_filepath );

		// Set time limit to 15 mins.
		@set_time_limit( 15 * MINUTE_IN_SECONDS );

		$logger = new Logger( 'main' );

		$stream = new StreamHandler( $log_filepath, Logger::DEBUG );

		$dateFormat = 'Y-m-d H:i:s';
		$output     = "%datetime% %level_name% %message%\n";
		$formatter  = new LineFormatter( $output, $dateFormat );

		$stream->setFormatter( $formatter );

		$logger->pushHandler( $stream );
		$logger->info( _x( 'Sync started', '', 'wpp-youtube' ) );

		// Set mutex so only one sync can run at a time.
		update_option( self::SYNC_MUTEX, time() );

		$logger->info( _x( 'Importing channel...', '', 'wpp-youtube' ) );

		$channel = $this->import_channel();

		if ( false === $channel ) {
			$logger->warn( _x( 'Channel import failed.', '', 'wpp-youtube' ) );
		} else {
			$logger->info( _x( 'Channel imported successfully.', '', 'wpp-youtube' ) );
		}

		$logger->info( _x( 'Importing playlists...', '', 'wpp-youtube' ) );

		$imported_term_ids = $this->import_playlists();

		$logger->info( sprintf(
			_x( 'Found and synced %d playlists.', '', 'wpp-youtube' ),
			( false === $imported_term_ids ) ? 0 : count( $imported_term_ids )
		) );

		$imported_post_ids = [];
		foreach ( $imported_term_ids as $imported_term_id ) {
			$playlist_id = get_term_meta( $imported_term_id, Playlist::META_PLAYLIST_ID, true );

			$logger->info( _x( 'Importing videos...', '', 'wpp-youtube' ) );

			$imported_post_ids = array_merge(
				$imported_post_ids,
				$this->import_videos_by_playlist_id( $playlist_id )
			);
		}

		$logger->info( sprintf(
			_x( 'Found and synced %d videos.', '', 'wpp-youtube' ),
			count( $imported_post_ids )
		) );

		$logger->info( _x( 'Cleaning up...', '', 'wpp-youtube' ) );

		$deleted_term_ids = [];

		if ( 'delete' === wpp_youtube()->options->get_orphaned_playlists() ) {
			$deleted_term_ids = $this->delete_playlists__not_in( $imported_term_ids );
		}

		$deleted_post_ids = [];
		$trashed_post_ids = [];

		if ( 'delete' === wpp_youtube()->options->get_orphaned_videos() ) {
			$deleted_post_ids = $this->delete_videos__not_in( $imported_post_ids );
		} elseif ( 'trash' === wpp_youtube()->options->get_orphaned_videos() ) {
			$trashed_post_ids = $this->trash_videos__not_in( $imported_post_ids );
		}

		// Clear mutex.
		delete_option( self::SYNC_MUTEX );

		$status = [
			'channel'            => $channel,
			'playlists_imported' => $imported_term_ids,
			'playlists_deleted'  => $deleted_term_ids,
			'videos_imported'    => $imported_post_ids,
			'videos_trashed'     => $trashed_post_ids,
			'videos_deleted'     => $deleted_post_ids,
		];

		$logger->info( _x( 'Import complete.', '', 'wpp-youtube' ) );

		return $status;
	}

	/**
	 * Import Channel details from YouTube.
	 *
	 * @param string $channel_id
	 *
	 * @return \Google_Service_YouTube_Channel|false
	 */
	public function import_channel( $channel_id = '' ) {

		if ( empty( $channel_id ) ) {
			$channel_id = wpp_youtube()->options->get_channel_id();
		}

		if ( empty( $channel_id ) ) {
			delete_option( Options::CHANNEL_KEY );

			return false;
		}

		$api = new API_Adapter();

		/**
		 * Google_Service_YouTube_Channel
		 *
		 * @var \Google_Service_YouTube_Channel $channel
		 */
		$channel = $api->get_channel_by_id( $channel_id );

		if ( false === $channel ) {
			delete_option( Options::CHANNEL_KEY );
		} else {
			update_option( Options::CHANNEL_KEY, $channel, false );
		}

		return $channel;
	}

	/**
	 * Import Playlists.
	 *
	 * Import all channel Playlists, including Channel uploads.
	 *
	 * @param \Google_Service_YouTube_Channel
	 *
	 * @return int[]|false
	 */
	public function import_playlists( $channel = null ) {

		$term_ids = [];

		if ( ! $channel ) {
			$channel = wpp_youtube()->options->get_channel();
		}

		if ( ! $channel instanceof \Google_Service_YouTube_Channel ) {
			return false;
		}

		$playlists = [];

		$uploads_playlist_id = $channel->getContentDetails()->getRelatedPlaylists()->getUploads();

		$api = new API_Adapter();

		$playlists[] = $api->get_playlist_by_id( $uploads_playlist_id );

		$playlists = array_merge(
			$playlists,
			$api->get_playlists_by_channel_id( wpp_youtube()->options->get_channel_id() )
		);

		if ( empty( $playlists ) ) {
			return [];
		}

		foreach ( $playlists as $playlist ) {
			$term_ids[] = $this->insert_or_update_playlist( $playlist );
		}

		return $term_ids;
	}

	/**
	 * Import videos from a playlist.
	 *
	 * @param $playlist_id
	 *
	 * @return int[] Post IDs.
	 */
	public function import_videos_by_playlist_id( $playlist_id ) {
		$api = new API_Adapter();

		$videos = $api->get_videos_by_playlist_id( $playlist_id );

		$post_ids = [];
		foreach ( $videos as $video ) {
			$post_ids[] = $this->insert_or_update_playlist_item( $video, $playlist_id );
		}

		return $post_ids;
	}

	/**
	 * Insert or update WordPress playlist term.
	 *
	 * @param \Google_Service_YouTube_Playlist $playlist
	 *
	 * @return int Term ID.
	 */
	private function insert_or_update_playlist( $playlist ) {

		// Check if Playlist exists (id)

		// Setup WP_Query args to check if this product already exists.
		// @see https://vip.wordpress.com/documentation/querying-on-meta_value/ for info on this query.
		$args = [
			'taxonomy'     => Playlist::TAXONOMY,
			'hide_empty'   => false,
			'meta_key'     => Playlist::META_PLAYLIST_ID_ . $playlist->getId(), /* phpcs:ignore */
			'meta_compare' => 'EXISTS',
		];

		// Run the WP_Term_Query.
		$query = new \WP_Term_Query( $args );

		if ( empty( $query->terms ) ) {
			// No? Insert.
			$term = wp_insert_term(
				$playlist->getSnippet()->title,
				Playlist::TAXONOMY,
				[
					'description' => $playlist->getSnippet()->description,
				]
			);

			// Add Meta.
			add_term_meta( $term['term_id'], Playlist::META_PLAYLIST_ID, $playlist->getId(), true );
			add_term_meta( $term['term_id'], Playlist::META_PLAYLIST_ID_ . $playlist->getId(), $playlist->getId(), true );
			add_term_meta( $term['term_id'], Playlist::META_PLAYLIST, $playlist, true );

			$term_id = $term['term_id'];
		} else {

			$term_id = $query->terms[0]->term_id;
			/**
			 * Get previous Google_Service_YouTube_Playlist object from term meta.
			 *
			 * @var \Google_Service_YouTube_Playlist $playlist_existing
			 */
			$playlist_existing = get_term_meta( $term_id, Playlist::META_PLAYLIST, true );

			if ( $playlist_existing->getEtag() !== $playlist->getEtag() ) {
				$term = wp_update_term(
					$query->terms[0]->term_id,
					Playlist::TAXONOMY,
					[
						'description' => $playlist->getSnippet()->description,
					]
				);

				add_term_meta( $term['term_id'], Playlist::META_PLAYLIST_ID, $playlist->getId(), true );
				add_term_meta( $term['term_id'], Playlist::META_PLAYLIST_ID_ . $playlist->getId(), $playlist->getId(), true );
				add_term_meta( $term['term_id'], Playlist::META_PLAYLIST, $playlist, true );

				$term_id = $term['term_id'];
			}
		}

		return $term_id;
	}

	/**
	 * Insert or update WordPress video post.
	 *
	 * @param \Google_Service_YouTube_PlaylistItem $item
	 *
	 * @return int Post ID.
	 */
	private function insert_or_update_playlist_item( $item, $playlist_id ) {

		$video_id = $item->getSnippet()->getResourceId()->getVideoId();

		$post_arr = [
			'post_title'   => $item->getSnippet()->getTitle(),
			'post_content' => $item->getSnippet()->getDescription(),
			'post_excerpt' => '',
			'post_status'  => 'publish',
			'post_type'    => Video::POST_TYPE,
		];

		$video_url = Video::youtube_url_from_id( $video_id ); // phpcs:ignore

		$meta_input = [
			Video::META_VIDEO_ID              => $video_id,
			Video::META_VIDEO_ID_ . $video_id => $video_id,
			Video::META_PLAYLIST_ITEM         => $item,
			Video::META_VIDEO_URL             => $video_url,
			Video::META_VIDEO_OEMBED          => wp_oembed_get( $video_url ),
		];

		// Insert post accepts meta_input directly.
		$post_arr['meta_input'] = $meta_input;

		// Setup WP_Query args to check if this product already exists.
		// @see https://vip.wordpress.com/documentation/querying-on-meta_value/ for info on this query.
		$args = [
			'meta_key'     => Video::META_VIDEO_ID_ . $video_id, // phpcs:ignore
			'meta_compare' => 'EXISTS',
			'post_type'    => Video::POST_TYPE,
		];

		// Run the WP_Query.
		$query = new \WP_Query( $args );

		if ( $query->have_posts() ) {
			$post_arr['ID'] = $query->post->ID;
		}

		$post_id = wp_insert_post( $post_arr, true );

		// Bail if error.
		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		// Add Thumbnail
		$attachment_id = $this->sideload_attachment(
			$this->get_video_thumb_url( $item ),
			[
				'attached_post_id'   => $post_id,
				'filename'           => sanitize_title_with_dashes( $item->getSnippet()->getTitle() . '-' . $video_id ),
				'file_ext'           => 'jpg',
				'post_title'         => $item->getSnippet()->getTitle(),
				Video::META_VIDEO_ID => $video_id,
			]
		);

		if ( ! is_wp_error( $attachment_id ) ) {
			update_post_meta( $post_id, '_thumbnail_id', $attachment_id );
		}

		// Add Taxonomy
		// Setup WP_Query args to check if this product already exists.
		// @see https://vip.wordpress.com/documentation/querying-on-meta_value/ for info on this query.
		$args = [
			'taxonomy'     => Playlist::TAXONOMY,
			'hide_empty'   => false,
			'meta_key'     => Playlist::META_PLAYLIST_ID_ . $playlist_id, /* phpcs:ignore */
			'meta_compare' => 'EXISTS',
		];

		// Run the WP_Term_Query.
		$query = new \WP_Term_Query( $args );

		if ( ! empty( $query->terms ) ) {
			wp_set_object_terms( $post_id, $query->terms[0]->term_id, Playlist::TAXONOMY, true );
		}

		return $post_id;
	}

	/**
	 * Sideload Asset by URL.
	 *
	 * @link   http://wordpress.stackexchange.com/a/145349/26350
	 *
	 * @param string $url             URL of image to import.
	 * @param array  $attachment_args Attachment arguments.
	 *
	 * @return int|\WP_Error $attachment_id The ID of the Attachment post or \WP_Error if failure.
	 */
	private function sideload_attachment( $url, $attachment_args = [] ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$parsed_url = wp_parse_url( $url );

		$args = wp_parse_args(
			$attachment_args,
			[
				'attached_post_id'   => 0,
				'filename'           => basename( $parsed_url['path'] ),
				'file_ext'           => false,
				'post_title'         => basename( $parsed_url['path'] ),
				Video::META_VIDEO_ID => false,
			]
		);

		// If we have an You ID, use it to check if the Attachment exists.
		if ( ! empty( $args['youtube_playlist_item_id'] ) ) {
			$query_args = [
				'meta_key'     => Video::META_VIDEO_ID_ . $args[ Video::META_VIDEO_ID ], /* phpcs:ignore */
				'meta_compare' => 'EXISTS',
				'post_type'    => 'attachment',
				'post_status'  => 'inherit',
			];

			// Run the WP_Query.
			$query = new \WP_Query( $query_args );
			if ( $query->have_posts() ) {
				// Attachment exists, return the ID.
				return $query->posts[0]->ID;
			}
		}

		// Set $file_ext via exif, if not provided explicitly via $args.
		$file_ext = ( empty( $args['file_ext'] ) )
			? \image_type_to_extension( \exif_imagetype( $url ), false )
			: $args['file_ext'];

		// Save as a temporary file.
		$temp_file = download_url( $url );

		// Check for download errors.
		if ( is_wp_error( $temp_file ) ) {
			return $temp_file;
		}

		// Get file path components.
		$pathinfo = pathinfo( $temp_file );

		// Rename with correct extension so media_handle_sideload() doesn't choke.
		if ( $file_ext !== $pathinfo['extension'] ) {
			$new_filepath = $pathinfo['dirname'] . DIRECTORY_SEPARATOR . $pathinfo['filename'] . '.' . $file_ext;

			$success = rename( $temp_file, $new_filepath );

			if ( ! $success ) {
				return new \WP_Error(
					'youtube-file-error',
					'Unable to rename temp file.',
					[ $pathinfo, $new_filepath ]
				);
			}

			$temp_file = $new_filepath;
		}

		// Upload the attachment into the WordPress Media Library with desired filename.
		$file_array = [
			'name'     => $args['filename'] . '.' . $file_ext,
			'tmp_name' => $temp_file,
		];

		$post_meta = [
			Video::META_VIDEO_ID                                  => $args[ Video::META_VIDEO_ID ], // phpcs:ignore
			Video::META_VIDEO_ID_ . $args[ Video::META_VIDEO_ID ] => $args[ Video::META_VIDEO_ID ],
			'_wp_attachment_image_alt'                            => $args['post_title'], // phpcs:ignore
		];

		$post_data = [
			'post_title'   => $args['post_title'] . '',
			'post_content' => $args['post_title'] . '',
			'post_excerpt' => $args['post_title'] . '',
			'meta_input'   => $post_meta,
		];

		$id = media_handle_sideload( $file_array, $args['attached_post_id'], null, $post_data );

		// Check for sideload errors.
		if ( is_wp_error( $id ) ) {
			unlink( $file_array['tmp_name'] );
		}

		return $id;
	}

	/**
	 * Get the largest image from the video object.
	 *
	 * @param \Google_Service_YouTube_PlaylistItem $item The video object.
	 *
	 * @return string The largest resolution URL in the video object.
	 */
	private function get_video_thumb_url( $item ) {
		$thumbnail = '';
		$snippet   = $item->getSnippet();
		if ( ! $snippet ) {
			return '';
		}
		$thumbnails = $snippet->getThumbnails();
		if ( ! $thumbnails ) {
			return '';
		}
		if ( ! empty( $thumbnails->getDefault() ) ) {
			$thumbnail = $thumbnails->getDefault();
		}
		if ( ! empty( $thumbnails->getMedium() ) ) {
			$thumbnail = $thumbnails->getMedium();
		}
		if ( ! empty( $thumbnails->getStandard() ) ) {
			$thumbnail = $thumbnails->getStandard();
		}
		if ( ! empty( $thumbnails->getHigh() ) ) {
			$thumbnail = $thumbnails->getHigh();
		}
		if ( ! empty( $thumbnails->getMaxres() ) ) {
			$thumbnail = $thumbnails->getMaxres();
		}

		return $thumbnail->getUrl();
	}

	/**
	 * Delete Playlist Terms not matching the provided Term IDs.
	 *
	 * @param $term_ids
	 *
	 * @return array
	 */
	private function delete_playlists__not_in( $term_ids ) {
		$delete_ids = $this->get_playlists__not_in( $term_ids );

		$deleted_ids = [];
		foreach ( $delete_ids as $key => $delete_id ) {
			$deleted_ids[ $key ] = wp_delete_term( $delete_id, Playlist::TAXONOMY );
		}

		$deleted_ids = array_filter( $deleted_ids );

		$deleted_ids = array_keys( $deleted_ids );

		return $deleted_ids;
	}

	/**
	 * Delete Video Posts not matching the provided Post IDs.
	 *
	 * @param $post_ids
	 *
	 * @return array
	 */
	private function delete_videos__not_in( $post_ids ) {
		$delete_ids = $this->get_videos__not_in( $post_ids );

		$deleted_posts = [];
		foreach ( $delete_ids as $delete_id ) {
			$deleted_posts[] = wp_delete_post( $delete_id );
		}

		$deleted_ids = array_map( function ( $post ) {
			if ( $post instanceof \WP_Post ) {
				return $post->ID;
			}

			return false;
		}, $deleted_posts );

		return $deleted_ids;
	}

	/**
	 * Trash Video Posts not matching the provided Post IDs.
	 *
	 * @param $post_ids
	 *
	 * @return array
	 */
	private function trash_videos__not_in( $post_ids ) {
		$trash_ids = $this->get_videos__not_in( $post_ids );

		$trashed_ids = [];
		foreach ( $trash_ids as $trash_id ) {
			$trashed_ids[] = wp_update_post( [
				'ID'          => $trash_id,
				'post_status' => 'trash',
			] );
		}

		return $trashed_ids;
	}

	/**
	 * Get a list of Playlist Term IDs not matching the provided IDs.
	 *
	 * @param $not_in
	 *
	 * @return array
	 */
	private function get_playlists__not_in( $not_in ) {
		$args = [
			'taxonomy'   => Playlist::TAXONOMY,
			'hide_empty' => false,
			'exclude'    => $not_in,
			'fields'     => 'ids',
		];

		$query = new \WP_Term_Query( $args );

		return $query->terms;
	}

	/**
	 * Get a list of Video Post IDs not matching the provided IDs.
	 *
	 * @param $not_in
	 *
	 * @return array
	 */
	private function get_videos__not_in( $not_in ) {
		$args = [
			'post_type'      => Video::POST_TYPE,
			'post__not_in'   => $not_in,
			'posts_per_page' => - 1,
			'fields'         => 'ids',
		];

		$query = new \WP_Query( $args );

		return $query->posts;
	}

	/**
	 * Get the log path, create if it doesn't exist.
	 *
	 * @return string
	 */
	private function get_log_path() {
		$upload_dir = wp_get_upload_dir();

		$base_dir = $upload_dir['basedir'];

		$log_path = trailingslashit( $base_dir ) . 'youtube-channel-sync';

		if ( ! file_exists( $log_path ) ) {
			mkdir( $log_path, 0755, true );
		}

		return $log_path;
	}

}