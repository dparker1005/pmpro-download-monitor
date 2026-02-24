=== Paid Memberships Pro - Download Monitor Integration Add On ===
Contributors: strangerstudios, paidmembershipspro
Tags: paid memberships pro, pmpro, membership, memberships, download monitor, restrict downloads
Requires at least: 5.4
Tested up to: 6.9
Requires PHP: 5.6
Stable tag: .2.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Require membership for downloads when using the Download Monitor plugin.

== Description ==

The Download Monitor Integration Add On for Paid Memberships Pro adds a "Require Membership" meta box to the "Edit Download" page, allowing you to easily toggle the membership level(s) that can access the download.

Requires Download Monitor (https://wordpress.org/plugins/download-monitor/) and Paid Memberships Pro installed and activated.

== Official Paid Memberships Pro Add On ==

This is an official Add On for [Paid Memberships Pro](https://www.paidmembershipspro.com), the most complete member management and membership subscriptions plugin for WordPress.

== Installation ==

= Prerequisites =
1. You must have Paid Memberships Pro and Download Monitor installed and activated on your site.

= Download, Install and Activate! =
1. Download the latest version of the plugin.
1. Unzip the downloaded file to your computer.
1. Upload the /pmpro-download-monitor/ directory to the /wp-content/plugins/ directory of your site.
1. Activate the plugin through the 'Plugins' menu in WordPress.

= How to Use =

1. After activation, navigate to "Downloads" to Edit or Add a New Download.
1. Check the box for each level that can access this download in the "Require Membership" meta box (below the Publish box in the right sidebar). 
1. Save your changes by clicking the "Update" button (or "Publish" if you are creating a new download).

== Changelog ==
= .2.1 =
* BUG FIX: Using the get_id() method to get the id of downloads now that the ->id property is private.

= .2 =
* ENHANCEMENT/FIX: Would show ':' if membership was required but level was inaccessible (signup allowed = no)

= .1 =
* Added unique templates for the output of the various download/downloads shortcodes.
* Added pmprodlm_shortcode_download_content_filter filter for non-member download shortcode output.
* Initial release.
