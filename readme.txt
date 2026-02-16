=== SRS Simple Hits Counter ===
Contributors: SandyRig
Tags: hits, visitor, counter, page-views
Requires at least: 3.4
Tested up to: 5.3.2
Stable tag: 1.1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Simple plugin to count and show a total number of hits (Unique visitors or page-views) to the site without using any third party code.

== Description ==

As the name suggests, it's a very simple Hit Counter that counts the number of Unique Visitors and Page-views. You can show either the Unique Visitors or total page-views count anywhere on the site using widgets or shortcode. You can also add/show both Counters by using two copies of the widget or the shortcode. It keeps counting even when you have no widget or shortcode active. To completely disable the counter you have to disable the plugin.

= Features =

* AJAX based counter ignores most bots or crawlers
* Monthly and weekly graph
* Show either the Unique Visitors or Page-views count
* Ability to reset the counter to any number any time
* Can be show anywhere on the site using Widgets and Shortcode

= SHORTCODES =

[srs_total_visitors] for Unique Visitors
[srs_total_pageViews] for Page-views

[DEMO](https://atif.rocks/srs-simple-hits-counter/)

== Installation ==

= From your WordPress dashboard =

1. Visit 'Plugins > Add New'
2. Search for 'SRS Simple Hits Counter'
3. Activate 'SRS Simple Hits Counter' from your Plugins page.
4. Use widget to display the counter in footer or sidebar etc.
5. Use the shortcode to add the counter to any part of the site.

= From WordPress.org =

1. Download 'SRS Simple Hits Counter'.
2. Upload the 'SRS Simple Hits Counter' directory to your '/wp-content/plugins/' directory, using your favorite method (ftp, sftp, scp, etc...)
3. Activate 'SRS Simple Hits Counter' from your Plugins page. 
4. Use widget to display the counter in footer or sidebar etc.
5. Use the shortcode to add the counter to any part of the site.


== Screenshots ==

1. Go to Appearance > Widgets and look for the widget 'SRS Simple Hits Counter' Select the widget area and click 'Add Widget'.
2. Select 'Visitors' radio button to show Unique vistors to your site.
3. Select 'Page Views' radio button to show count for every page load.
4. Widget Demo

== Changelog ==

= 1.1.0 - 20th JULY 2020 =
* Added the ability to reset plugin data deleting all the existing data in the plugin's database table and start fresh
* Security and other updates
* Graph libraries updated

= 1.0.5 - 17th JULY 2020 =
* Critical Security Update

= 1.0.4 - 6th JULY 2020 =
* Security Updates

= 1.0.3 - 4th JUNE 2018 =
* Removed PHP Session based user tracking if favor of session cookie to help with caching
* Moved js files to load in footer to improve Google PageSpeed score by avoiding render-blocking.

= 1.0.2 - 14th AUG 2017 =
* Critical update, fixed migration bug in 1.0.1

= 1.0.1 - 8th AUG 2017 =
* Critical bug fix

= 1.0.0 - 8th AUG 2017 =
* Added weekly and monthly graph

= 0.1.6 - 2nd AUG 2017 =
* Added the option to show commas in the counter
* Changed to Ajax based counting to filter out bots and crawlers 

= 0.1.5 - 6th DEC 2016 =
* Created options page under the settings menu and moved counter reset settings to that page.

= 0.1.4 - 17th AUG 2015 =
* Removed the extra closing tag for page-views shortcode.

= 0.1.3 - 4th Feb 2015 =
* Added CSS classes to identify and add custom style to the counters span tags.

= 0.1.2 - 24th Jan 2015 =
* Critical - Unique Visitors bug fix.

= 0.1.1 - 18th Dec 2014 =
* Fixed a PHP notice.

= 0.1.0 - 17th Dec 2014 =
* Simple counter to show Unique Visitors or Page-views on your site.