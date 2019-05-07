<?php
/**
 * Filename class-api-adapter.php
 *
 * @package WP_Perf\YouTube_Channel_Sync
 * @author  WP Perf <wpperf@gmail.com>
 * @since   1.0.0
 */

namespace WP_Perf\YouTube_Channel_Sync;

/**
 * Class API_Adapter
 *
 * Adapter for \Google_Client
 *
 * @package WP_Perf\YouTube_Channel_Sync
 * @since   1.0.0
 */
class API_Adapter {

	const MAX_CALLS = 100;

	const MAX_RESULTS = 50;

	/**
	 * API_Adapter constructor.
	 *
	 * @param string $api_key Google API Key.
	 */
	public function __construct() {
	}

	/**
	 * Get YouTube Channel by Channel ID.
	 *
	 * @param $channel_id
	 *
	 * @return \Google_Service_YouTube_Channel|false
	 */
	public function get_channel_by_id( $channel_id ) {
		$client = new \Google_Client();
		$client->setApplicationName( __NAMESPACE__ );
		$client->setDeveloperKey( wpp_youtube()->options->get_api_key() );

		$service = new \Google_Service_YouTube( $client );

		$params = [
			'id'         => $channel_id,
			'maxResults' => 1,
		];

		if ( ! empty( $next_page_token ) ) {
			$params['pageToken'] = $next_page_token;
		}

		$response = $service->channels->listChannels( 'contentDetails,snippet,status', $params );

		if ( 0 === $response->getPageInfo()->getTotalResults() ) {
			$channel = false;
		} else {
			$channel = $response->getItems()[0];
		}

		unset( $client );
		unset( $service );
		unset( $response );

		return $channel;
	}

	/**
	 * @param $playlist_id
	 *
	 * @return \Google_Service_YouTube_Playlist
	 */
	public function get_playlist_by_id( $playlist_id ) {
		$client = new \Google_Client();
		$client->setApplicationName( __NAMESPACE__ );
		$client->setDeveloperKey( wpp_youtube()->options->get_api_key() );

		$service = new \Google_Service_YouTube( $client );

		$params = [
			'id'         => $playlist_id,
			'maxResults' => 1,
		];

		if ( ! empty( $next_page_token ) ) {
			$params['pageToken'] = $next_page_token;
		}

		$response = $service->playlists->listPlaylists( 'player,snippet,status', $params );

		if ( 0 === $response->getPageInfo()->getTotalResults() ) {
			$playlist = false;
		} else {
			$playlist = $response->getItems()[0];
		}


		unset( $client );
		unset( $service );
		unset( $response );

		return $playlist;
	}

	/**
	 * @param $channel_id
	 *
	 * @return \Google_Service_YouTube_Playlist[]
	 */
	public function get_playlists_by_channel_id( $channel_id ) {
		$playlists       = [];
		$attempts        = 0;
		$next_page_token = false;

		$client = new \Google_Client();
		$client->setApplicationName( __NAMESPACE__ );
		$client->setDeveloperKey( wpp_youtube()->options->get_api_key() );

		$service = new \Google_Service_YouTube( $client );

		do {
			$params = [
				'channelId'  => $channel_id,
				'maxResults' => self::MAX_RESULTS,
			];

			if ( ! empty( $next_page_token ) ) {
				$params['pageToken'] = $next_page_token;
			}

			$response = $service->playlists->listPlaylists( 'player,snippet,status', $params );

			$playlists = array_merge( $playlists, $response->getItems() );

			$next_page_token = $response->getNextPageToken();

		} while ( ! empty( $next_page_token ) && $attempts ++ < self::MAX_CALLS );

		unset( $client );
		unset( $service );
		unset( $response );

		return $playlists;
	}

	/**
	 * Get YouTube Playlist Items by Playlist ID.
	 *
	 * @param $playlist_id
	 */
	public function get_videos_by_playlist_id( $playlist_id ) {
		$items           = [];
		$attempts        = 0;
		$next_page_token = false;

		$client = new \Google_Client();
		$client->setApplicationName( __NAMESPACE__ );
		$client->setDeveloperKey( wpp_youtube()->options->get_api_key() );

		$service = new \Google_Service_YouTube( $client );

		do {
			$params = [
				'playlistId' => $playlist_id,
				'maxResults' => self::MAX_RESULTS,
			];

			if ( ! empty( $next_page_token ) ) {
				$params['pageToken'] = $next_page_token;
			}

			$response = $service->playlistItems->listPlaylistItems( 'contentDetails,snippet,status', $params );

			$items = array_merge( $items, $response->getItems() );

			$next_page_token = $response->getNextPageToken();

		} while ( ! empty( $next_page_token ) && $attempts ++ < self::MAX_CALLS );

		unset( $client );
		unset( $service );
		unset( $response );

		return $items;
	}
}
