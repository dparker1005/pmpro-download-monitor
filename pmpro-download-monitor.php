<?php
/**
 * Plugin Name: Paid Memberships Pro - Download Monitor Integration Add On
 * Plugin URI: https://www.paidmembershipspro.com/add-ons/pmpro-download-monitor/
 * Description: Require membership for downloads when using the Download Monitor plugin.
 * Version: .2.1
 * Author: Paid Memberships Pro
 * Author URI: https://www.paidmembershipspro.com
 * Text Domain: pmpro-download-monitor
 * Domain Path: /languages
 * License: GPLv2 or later
 */

/**
 * Add the dlm_download CPT to the list of PMPro restrictable post types.
 *
 * @since TBD
 *
 * @param array $post_types Array of post types that PMPro can restrict.
 * @return array Modified array of restrictable post types.
 */
function pmprodlm_restrictable_post_types( $post_types ) {
	$post_types[] = 'dlm_download';
	return array_unique( $post_types );
}
add_filter( 'pmpro_restrictable_post_types', 'pmprodlm_restrictable_post_types' );

/**
 * Get the membership level IDs and names required for a download.
 *
 * Filters out levels that do not allow signups unless overridden
 * by the pmpro_membership_content_filter_disallowed_levels filter.
 *
 * @since .1
 *
 * @param DLM_Download $dlm_download The Download Monitor download object.
 * @return array {
 *     Array with two elements.
 *
 *     @type int[]  $0 Array of membership level IDs.
 *     @type string $1 Human-readable level names joined with "or".
 * }
 */
function pmprodlm_getDownloadLevels($dlm_download)
{
	$hasaccess = pmpro_has_membership_access($dlm_download->get_id(), NULL, true);
	if(is_array($hasaccess))
	{
		//returned an array to give us the membership level values
		$download_membership_levels_ids = $hasaccess[1];
		$download_membership_levels_names = $hasaccess[2];
		$hasaccess = $hasaccess[0];
	}
	if(empty($download_membership_levels_ids))
		$download_membership_levels_ids = array();
	if(empty($download_membership_levels_names))
		$download_membership_levels_names = array();

	 //hide levels which don't allow signups by default
	if(!apply_filters("pmpro_membership_content_filter_disallowed_levels", false, $download_membership_levels_ids, $download_membership_levels_names))
	{
			foreach($download_membership_levels_ids as $key=>$id)
			{
				//does this level allow registrations?
				$level_obj = pmpro_getLevel($id);
				if(empty($level_obj) || !$level_obj->allow_signups)
				{
					unset($download_membership_levels_ids[$key]);
					unset($download_membership_levels_names[$key]);
				}
			}
	}

	$download_membership_levels_names = pmpro_implodeToEnglish($download_membership_levels_names, 'or');
	
	return array($download_membership_levels_ids, $download_membership_levels_names);
}

/**
 * Check if the current user has membership access to a download.
 *
 * Hooked to dlm_can_download to block direct download access
 * for users without the required membership level.
 *
 * @since .1
 *
 * @param bool         $can_download Whether the user can download the file.
 * @param DLM_Download $download     The Download Monitor download object.
 * @return bool Whether the user can download the file.
 */
function pmprodlm_can_download( $can_download, $download ) {
	if ( !$can_download ) {
		return $can_download;
	}

	if ( function_exists( 'pmpro_hasMembershipLevel' ) ) {
		$download_id = ( is_object( $download ) && method_exists( $download, 'get_id' ) ) ? $download->get_id() : 0;
		if ( empty( $download_id ) ) {
			return $can_download;
		}

		//check for membership
		if ( !pmpro_has_membership_access($download_id) ) {
			$can_download = false;
		}
	}
	return $can_download;
}
add_filter( 'dlm_can_download', 'pmprodlm_can_download', 10, 2 );

/**
 * Swap DLM download templates for membership-restricted versions.
 *
 * When a download requires membership and the current user does not have access,
 * this replaces the standard DLM template with a style-matched restricted template.
 * Supports default, title, box, button, and filename template variants.
 *
 * Legacy pmpro_* template names are normalized and trigger a _doing_it_wrong notice.
 *
 * @since TBD
 *
 * @param string $template The path to the template file.
 * @param string $slug     The template slug (e.g. 'content-download').
 * @param string $name     The template name/variant (e.g. 'box', 'button').
 * @param array  $args     Template arguments including the download object.
 * @return string The path to the original or restricted template file.
 */
function pmprodlm_dlm_get_template_part( $template, $slug, $name, $args = array() ) {
	// Only handle download content templates, not no-access, tc-form, etc.
	if ( 'content-download' !== $slug ) {
		return $template;
	}

	// Only proceed if PMPro is active and we have a download to check.
	if ( ! function_exists( 'pmpro_hasMembershipLevel' ) ) {
		return $template;
	}

	// DLM passes the download as either 'dlm_download' or 'download' depending on the caller.
	$dlm_download = isset( $args['dlm_download'] ) ? $args['dlm_download'] : ( isset( $args['download'] ) ? $args['download'] : null );
	if ( empty( $dlm_download ) || ! is_object( $dlm_download ) || ! method_exists( $dlm_download, 'get_id' ) ) {
		return $template;
	}

	// Normalize name: strip pmpro_ prefix, treat plain 'pmpro' as default.
	$variant = str_replace( 'pmpro_', '', $name );
	if ( 'pmpro' === $variant ) {
		$variant = '';
	}

	// Flag misuse of internal pmpro_ template names in shortcode/block attributes.
	if ( $name !== $variant ) {
		$suggested = ! empty( $variant ) ? 'template="' . $variant . '"' : 'the default template';
		_doing_it_wrong(
			'[download] template attribute',
			sprintf(
				/* translators: 1: old template name, 2: suggested replacement */
				esc_html__( 'The "%1$s" template should no longer be used directly. Use %2$s instead.', 'pmpro-download-monitor' ),
				esc_html( $name ),
				esc_html( $suggested )
			),
			'TBD'
		);
	}

	// If user has access, return the DLM template unchanged.
	if ( pmpro_has_membership_access( $dlm_download->get_id() ) ) {
		return $template;
	}

	// User doesn't have access. Swap to a style-matched restricted template.
	$pmpro_templates_dir = trailingslashit( dirname( __FILE__ ) ) . 'templates/';

	// Look for a style-matched restricted template, fall back to default.
	if ( ! empty( $variant ) && file_exists( $pmpro_templates_dir . 'content-download-pmpro_' . $variant . '.php' ) ) {
		$template = $pmpro_templates_dir . 'content-download-pmpro_' . $variant . '.php';
	} else {
		$template = $pmpro_templates_dir . 'content-download-pmpro.php';
	}

	return $template;
}
add_filter( 'dlm_get_template_part', 'pmprodlm_dlm_get_template_part', 10, 4 );

/**
 * Display the PMPro no-access message on the DLM no-access page.
 *
 * Shows the appropriate membership required message when a user
 * is denied access to a download. Uses pmpro_get_no_access_message()
 * on PMPro 3.1+ and falls back to legacy message handling for older versions.
 *
 * @since .1
 *
 * @param DLM_Download $download The Download Monitor download object.
 */
function pmprodlm_dlm_no_access_after_message($download) {
	global $current_user;
	if ( function_exists( 'pmpro_hasMembershipLevel' ) ) {
		if ( !pmpro_has_membership_access( $download->get_id() ) ) 
		{
			$hasaccess = pmpro_has_membership_access($download->get_id(), NULL, true);
			if(is_array($hasaccess))
			{
				//returned an array to give us the membership level values
				$post_membership_levels_ids = $hasaccess[1];
				$post_membership_levels_names = $hasaccess[2];
				$hasaccess = $hasaccess[0];
			}

			// PMPro 3.1+ message handling.
			if ( function_exists( 'pmpro_get_no_access_message' ) ) {
				if(empty($post_membership_levels_ids))
					$post_membership_levels_ids = array();
				if(empty($post_membership_levels_names))
					$post_membership_levels_names = array();

				echo pmpro_get_no_access_message( '', $post_membership_levels_ids, $post_membership_levels_names );
				return;
			}

			if(empty($post_membership_levels_ids))
				$post_membership_levels_ids = array();
			if(empty($post_membership_levels_names))
				$post_membership_levels_names = array();
		
			 //hide levels which don't allow signups by default
			if(!apply_filters("pmpro_membership_content_filter_disallowed_levels", false, $post_membership_levels_ids, $post_membership_levels_names))
			{
				foreach($post_membership_levels_ids as $key=>$id)
				{
					//does this level allow registrations?
					$level_obj = pmpro_getLevel($id);
					if(!$level_obj->allow_signups)
					{
						unset($post_membership_levels_ids[$key]);
						unset($post_membership_levels_names[$key]);
					}
				}
			}
		
			$pmpro_content_message_pre = '<div class="pmpro_content_message">';
			$pmpro_content_message_post = '</div>';
			$content = '';
			$sr_search = array("!!levels!!", "!!referrer!!");
			$sr_replace = array(pmpro_implodeToEnglish($post_membership_levels_names), urlencode(site_url($_SERVER['REQUEST_URI'])));
			//get the correct message to show at the bottom
			if($current_user->ID)
			{
				//not a member
				$newcontent = apply_filters("pmpro_non_member_text_filter", stripslashes(get_option("pmpro_nonmembertext")));
				$content .= $pmpro_content_message_pre . str_replace($sr_search, $sr_replace, $newcontent) . $pmpro_content_message_post;
			}
			else
			{
				//not logged in!
				$newcontent = apply_filters("pmpro_not_logged_in_text_filter", stripslashes(get_option("pmpro_notloggedintext")));
				$content .= $pmpro_content_message_pre . str_replace($sr_search, $sr_replace, $newcontent) . $pmpro_content_message_post;
			}
		}
	}
	echo $content;	
}
add_action('dlm_no_access_after_message', 'pmprodlm_dlm_no_access_after_message', 10, 2);

/**
 * Add documentation and support links to the plugin row meta.
 *
 * @since .1
 *
 * @param array  $links Array of existing plugin row meta links.
 * @param string $file  Path to the plugin file relative to the plugins directory.
 * @return array Modified array of plugin row meta links.
 */
function pmprodlm_plugin_row_meta($links, $file) {
	if(strpos($file, 'pmpro-download-monitor.php') !== false)
	{
		$new_links = array(
			'<a href="' . esc_url('https://www.paidmembershipspro.com/add-ons/pmpro-download-monitor/')  . '" title="' . esc_attr__( 'View Documentation', 'pmpro-download-monitor' ) . '">' . esc_html__( 'Docs', 'pmpro-download-monitor' ) . '</a>',
			'<a href="' . esc_url('https://www.paidmembershipspro.com/support/') . '" title="' . esc_attr__( 'Visit Customer Support Forum', 'pmpro-download-monitor' ) . '">' . esc_html__( 'Support', 'pmpro-download-monitor' ) . '</a>',
		);
		$links = array_merge($links, $new_links);
	}
	return $links;
}
add_filter('plugin_row_meta', 'pmprodlm_plugin_row_meta', 10, 2);
