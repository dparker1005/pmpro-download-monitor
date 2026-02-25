<?php
/**
 * Restricted download output (box style).
 *
 * Shown when the current user does not have membership access to the download.
 * Matches the structure of Download Monitor's content-download-box.php template.
 *
 * @version 1.0
 *
 * @var DLM_Download       $dlm_download   The download object.
 * @var Attributes         $dlm_attributes The shortcode attributes.
 * @var TemplateAttributes $attributes     The template attributes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! isset( $dlm_download ) || ! $dlm_download ) {
	return esc_html__( 'No download found', 'download-monitor' );
}

// Assign the template to a variable for easier access.
$template = __FILE__;

// This is a fix for the Gutenberg block to ensure the added classes are loaded.
if ( ! empty( $dlm_attributes['className'] ) ) {
	$attributes['link_attributes']['class'][] = $dlm_attributes['className'];
}

// Get the required membership levels and override the link.
$download_membership_levels = pmprodlm_getDownloadLevels( $dlm_download );
if ( count( $download_membership_levels[0] ) > 1 || empty( $download_membership_levels[0][0] ) ) {
	$attributes['link_attributes']['href'] = esc_url( pmpro_url( 'levels' ) );
} else {
	$attributes['link_attributes']['href'] = esc_url( pmpro_url( 'checkout', '?level=' . $download_membership_levels[0][0], 'https' ) );
}
unset( $attributes['link_attributes']['rel'] );
?>
<aside
	class="download-box<?php
	echo ( ! empty( $dlm_attributes['className'] ) ) ? ' ' . esc_attr( $dlm_attributes['className'] ) : ''; ?>">

	<?php $dlm_download->the_image(); ?>

	<div
		class="download-count"><?php
		printf( esc_html( _n( '1 download', '%d downloads', $dlm_download->get_download_count(), 'download-monitor' ) ), esc_html( $dlm_download->get_download_count() ) ); ?></div>

	<div
		class="download-box-content">

		<h1><?php $dlm_download->the_title(); ?></h1>

		<?php $dlm_download->the_excerpt(); ?>
		<?php

		/**
		 * Hook: dlm_template_content_before_link.
		 *
		 * @param DLM_Download $dlm_download The download object.
		 * @param array        $attributes   The template attributes.
		 * @param string       $template     The template file.
		 *
		 * @since 4.9.6
		 */
		do_action( 'dlm_template_content_before_link', $dlm_download, $attributes, $template );
		?>
		<a <?php echo DLM_Utils::generate_attributes( $attributes['link_attributes'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
			<?php echo esc_html__( 'Membership Required', 'pmpro-download-monitor' ); ?>
			<?php if ( ! empty( $download_membership_levels[1] ) ) { ?>
				<small><?php echo esc_html( $download_membership_levels[1] ); ?></small>
			<?php } ?>
		</a>
		<?php

		/**
		 * Hook: dlm_template_content_after_link.
		 *
		 * @param DLM_Download $dlm_download The download object.
		 * @param array        $attributes   The template attributes.
		 * @param string       $template     The template file.
		 *
		 * @since 4.9.6
		 */
		do_action( 'dlm_template_content_after_link', $dlm_download, $attributes, $template );
		?>
	</div>
</aside>
