=== ACF Flexible Columns ===
Contributors: imageDESIGN
Tags: columns,acf,advanced custom fields, bootstrap, animate on scroll, slider, carousel
Requires at least: 4.5
Tested up to: 4.9.5
Stable tag: 1.1.7
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Replace the regular single content editor with responsive multiple column editors.

== Description ==
This plugin will replace your default content editor with flexible multiple column editors, allowing you to add up to 4 columns of content to your page.  Column widths can be easily altered and are responsive to mobile, tablet and desktop device sizes.  This plugin requires the PRO version of Advanced Custom Fields to work as it makes use of  Flexible Content fields.

== Installation ==
1. Upload plugin to your wp-content/plugins/ directory, or install via the Plugins section within your WordPress installation.
1. Ensure that Advanced Custom Fields PRO is installed and activated in your plugins
1. Activate the ACF Flexible Columns plugin
1. On the Plugins page under the ACF Flexible Columns plugin, right-click on the JSON Import File link and Save the file to your computer.
1. Navigate to the Custom Fields > Tools page and under Import Field Groups, click Browse and select the acf-flexible-columns.json file you downloaded, then click Import
1. Flexible Columns are now fully installed and are available in your Page and Post editors, any existing content is retained in a new single column.
1. You can easily migrate your existing content into the new editor system within the options panel within Custom Fields > Flexible Columns.
1. Additional options are available in the Custom Fields > Flexible Columns admin panel

== Frequently Asked Questions ==

= How to add your own custom Content Block =
1. First, add your new content block within the Advanced Custom Fields - Flexible Columns editor by adding a new layout within the row width(s) you want it available in.
1. Next, create a new function in your functions.php like so:
	function yourfunction($type){
		if( $type == 'layout_name' ):
			$field = get_sub_field('field_name');
			$layout = $field;
		endif;
		return $layout;
	}
1. Then add the function to the filter so it will appear like so:
	add_filter('flexible_layout', 'yourfunction');

= See Settings page for additional filter examples =

== Screenshots ==
1. Row & Column Editors
2. Front End Display

== Changelog ==

= 1.1.7 =
* Fixed issue with List columns not working on ol tags, added Download link on plugins page for JSON import file and removed Sync functions as they were no longer working properly

= 1.1.6 =
* Fixed issue with fluid containers not working properly

= 1.1.5 =
* Add option to remove embedded styles for list item columns and moved embeded styles to head to prevent validation errors

= 1.1.4 =
* Fixed full-width row class for Bootstrap 4 (changed from col-xs-12 to col-sm-12)

= 1.1.3 =
* Added additional class filters for columns and rows
* Added option for enabling outer .container & .container-fluid around the column rows for better full width support

= 1.1.1 =
* fixed filter errors for missing 2nd parameter
* fixed extra row div around carousel