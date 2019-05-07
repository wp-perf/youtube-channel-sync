<?php
/**
 * Filename field-channel-preview.php
 *
 * @package demo
 * @author  Peter Toi <peter@petertoi.com>
 */

$channel = wpp_youtube()->options->get_channel();
?>

<div class="wpp-youtube-channel-preview">
	<?php if ( $channel instanceof \Google_Service_YouTube_Channel ) : ?>
		<?php
		$thumb_url = $channel->getSnippet()->getThumbnails()->getDefault()->getUrl();
		$title     = $channel->getSnippet()->getTitle();
		$desc      = $channel->getSnippet()->getDescription();
		?>
		<div class="wpp-youtube-channel-preview-thumb">
			<img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( $title ); ?>">
		</div>
		<div class="wpp-youtube-channel-preview-details">
			<p><strong><?php echo esc_html( $title ); ?></strong></p>
			<p><?php echo esc_html( $desc ); ?></p>
		</div>
	<?php else : ?>
		<p><?php _ex( 'Please enter a valid API Key and YouTube Channel ID.', '', 'wpp-youtube' ); ?></p>
	<?php endif; ?>
</div>
