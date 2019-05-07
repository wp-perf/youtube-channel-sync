<?php
/**
 * Filename video-meta-box.php
 *
 * @package WP_Perf\YouTube_Channel_Sync
 * @since   1.0.0
 */

/**
 * Data passed via Template::load();
 *
 * @var array $data Data passed via Template::load();
 */

use WP_Perf\YouTube_Channel_Sync\Template;
use WP_Perf\YouTube_Channel_Sync\Post_Types\Video;

?>
<div class="wrapper">
	<table class="form-table">
		<tbody>
		<?php
		echo Template::render_admin_table_row(
			_x( 'YouTube  URL', '', 'wpp-youtube' ),
			Template::render_input(
				'text',
				Video::META_VIDEO_URL,
				Video::META_VIDEO_URL,
				$data[ Video::META_VIDEO_URL ],
				[
					'class'    => 'large-text',
					'readonly' => 'readonly',
				]
			)
		);

		echo Template::render_admin_table_row(
			_x( 'Preview', '', 'wpp-youtube' ),
			empty( $data[ Video::META_VIDEO_OEMBED ] )
				? _x( 'No video preview.', '', 'wpp-youtube' )
				: Video::render_shortcode( [ 'id' => get_the_ID() ] )
		);
		?>
		</tbody>
	</table>
</div>

