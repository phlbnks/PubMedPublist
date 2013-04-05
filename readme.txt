=== PubMed Publist ===
Contributors: emirpprime
Tags: PubMed, publications
Requires at least: 3.5
Tested up to: 3.6
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html


Simple shortcode to get and display latest publications from PubMed using a PubMed search URL(s).


== Description ==

This plugin will register a shortcode [recentpublications] that will display the latest publications retrived from PubMed.

The shortcode accepts arguments to set how many papers are displayed, apply classes for custom styling, and choose from two layout options.


= Roadmap =

* Neaten up admin - show/hide for extra search string fields
* Make caching configurable
* Add widget version
* Add JS for show/hide extra results on front-end


== Installation ==

1. Upload the `pm_publist` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Visit `Settings -> PubMed Publist` to configure the plugin and see the shortcode arguments available.
1. Use `[recentpublications]` in your pages/posts where you want it to display
1. Or use `<?php do_shortcode('recentpublications'); ?>` in your templates


== Frequently Asked Questions ==

= What are the layouts available =

You can see the layout options on the [Screenshots](screenshots) page. After installing they are also shown on the Settings page.

= Can I contribute to the plugin / suggest a change? =

Please do! [The project is hosted on GitHub here](http://github.com/emirpprime/PubMedPublist).


== Screenshots ==

1. Using the shortcode in a post - showing all optional arguments being used
1. Default output (using Twenty Thirteen theme) with no arguments
1. Layout options
1. Settings screen


== Changelog ==

= 0.9.1 =
* Clean up PHP errors.
* Migrate Options to an array, it's good manners.

= 0.9 =
* Initial Beta Release
