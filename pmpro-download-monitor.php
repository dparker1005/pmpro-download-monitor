<?php
/**
 * Plugin Name: Paid Memberships Pro - Download Monitor Integration Add On
 * Plugin URI: https://www.paidmembershipspro.com/add-ons/pmpro-download-monitor/
 * Description: Require membership for downloads when using the Download Monitor plugin.
 * Version: .2.1
 * Author: Paid Memberships Pro
 * Author URI: https://www.paidmembershipspro.com
 * Text Domain: pmpro-download-monitor
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

/*
 * Add Require Membership box to dlm_download CPT
 */
function pmprodlm_page_meta_wrapper() {
	add_meta_box( 'pmpro_page_meta', esc_html__( 'Require Membership', 'pmpro-download-monitor' ), 'pmpro_page_meta', 'dlm_download', 'side' );
}
function pmprodlm_restrictable_post_types( $post_types ) {
	$post_types[] = 'dlm_download';
	return array_unique( $post_types );
}
function pmprodlm_cpt_init() {
	// PMPro versions with this REST route also support the pmpro_restrictable_post_types
	// filter. Older PMPro versions need the legacy admin_menu metabox fallback.
	if ( class_exists( 'PMPro_REST_API_Routes' ) && method_exists( 'PMPro_REST_API_Routes', 'pmpro_rest_api_get_post_restrictions' ) )
		add_filter( 'pmpro_restrictable_post_types', 'pmprodlm_restrictable_post_types' );
	elseif ( is_admin() )
		add_action( 'admin_menu', 'pmprodlm_page_meta_wrapper' );
}
add_action( "init", "pmprodlm_cpt_init", 20 );

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

/*
 * Require Membership on the Download
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

function pmprodlm_get_download( $download_id ) {
	$download_id = intval( $download_id );
	if ( empty( $download_id ) ) {
		return false;
	}

	if ( function_exists( 'download_monitor' ) ) {
		try {
			$dlm_download = download_monitor()->service( 'download_repository' )->retrieve_single( $download_id );
			if ( !empty( $dlm_download ) && $dlm_download->exists() ) {
				return $dlm_download;
			}
		} catch ( Exception $e ) {
		}
	}

	if ( class_exists( 'DLM_Download' ) ) {
		$dlm_download = new DLM_Download( $download_id );
		if ( !empty( $dlm_download ) && $dlm_download->exists() ) {
			return $dlm_download;
		}
	}

	return false;
}

function pmprodlm_dlm_get_template_part( $template, $slug, $name ) {	
	if($name == 'pmpro')
	{
		$template = trailingslashit( dirname(__FILE__) ) . "templates/content-download-pmpro.php";
	}
	elseif(strpos($name, "pmpro_") !== false)
	{
		$template = trailingslashit( dirname(__FILE__) ) . "templates/content-download-" . $name . ".php";
	}
	return $template;
}
add_filter('dlm_get_template_part', 'pmprodlm_dlm_get_template_part', 10, 3);

function pmprodlm_shortcode_download_content( $content, $download_id, $atts ) {
	global $current_user;
	if(empty($atts['template']) && (function_exists( 'pmpro_hasMembershipLevel' )) ) {
		if ( !pmpro_has_membership_access( $download_id ) )
		{
			$dlm_download = pmprodlm_get_download( $download_id );
			if ( !empty( $dlm_download ) && $dlm_download->exists() )
			{
				$download_membership_levels = pmprodlm_getDownloadLevels($dlm_download);
				$content .= '<a href="';
				if(count($download_membership_levels[0]) > 1 || empty($download_membership_levels[0][0]))
					$content .= esc_url( pmpro_url('levels') );
				else
					$content .= esc_url( pmpro_url("checkout", "?level=" . $download_membership_levels[0][0], "https") );
				$content .= '">' . esc_html( $dlm_download->get_the_title() ) . '</a>';
				$content .= ' (' . esc_html__( 'Membership Required', 'pmpro-download-monitor' ) . ': ' . esc_html( $download_membership_levels[1] ) . ')';
				$content = apply_filters("pmprodlm_shortcode_download_content_filter", $content);
			} 
			else 
			{
				$content = '[' . esc_html__( 'Download not found', 'pmpro-download-monitor' ) . ']';
			}
		}
	}
	return $content;
}
add_filter('dlm_shortcode_download_content', 'pmprodlm_shortcode_download_content', 10, 3);

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

/*
Function to add links to the plugin row meta
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
