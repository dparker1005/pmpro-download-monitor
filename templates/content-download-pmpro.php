<?php
/**
 * PMPro custom template output for a download via the [download] shortcode
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly
global $current_user;
if ( function_exists( 'pmpro_hasMembershipLevel' ) ) {
	if ( !pmpro_has_membership_access( $dlm_download->get_id() ) ) 
	{
		$download_membership_levels = pmprodlm_getDownloadLevels($dlm_download);
	
		if ( $dlm_download->exists() ) {
			?>
			<a class="download-link" href="<?php
				if(count($download_membership_levels[0]) > 1 || empty($download_membership_levels[0][0]))
					echo esc_url( pmpro_url('levels') );
				else
					echo esc_url( pmpro_url("checkout", "?level=" . $download_membership_levels[0][0], "https") );
			?>"><?php echo esc_html( $dlm_download->get_the_title() ); ?></a>
			<?php esc_html_e( 'Membership Required', 'pmpro-download-monitor' ); ?><?php echo !empty($download_membership_levels[1]) ? ': ' : null; ?><?php echo esc_html( $download_membership_levels[1] ); ?>
			<?php	
		} 
		else 
		{
			?>
			[<?php esc_html_e( 'Download not found', 'pmpro-download-monitor' ); ?>]
			<?php
		}
	}
	else
	{
		?>
		<a class="download-link" title="<?php if ( $dlm_download->has_version_number() ) {
		printf( esc_attr__( 'Version %s', 'download-monitor' ), esc_attr( $dlm_download->get_the_version_number() ) );
		} ?>" href="<?php $dlm_download->the_download_link(); ?>" rel="nofollow">
		<?php $dlm_download->the_title(); ?>
		(<?php printf( _n( '1 download', '%d downloads', $dlm_download->get_the_download_count(), 'download-monitor' ), $dlm_download->get_the_download_count() ) ?>)
		</a>
		<?php
	}
}
