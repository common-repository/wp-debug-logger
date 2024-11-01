=== WP Debug Logger ===
Contributors: donncha, automattic
Tags: debug, log, development, php
Tested up to: 4.7
Stable tag: 0.1
Requires at least: 2.9.2

A plugin that makes it easy to log code activity to a file.

== Description ==

This plugin logs the activity of supported plugins to a file, making it easier to figure out why there's a problem.

If you have been asked to install this plugin because of a support issue then installing this plugin is just like installing any other plugin. Once installed, go to Settings->WP Logger and enable logging. If you have a number of supported plugins enable the one you are interested in.

Developers who want to use this plugin to add logging to their own plugin should see the Developers section of this documentation.

Mark Jaquith's [Monitor Pages](http://wordpress.org/extend/plugins/monitor-pages/) plugin was used as a template for this plugin. Thanks Mark!

= Developers =

Other plugins can use this plugin to record important events which are then dumped to a log file. Plugins must add the event to a global array in the following way:

`$GLOBALS[ 'wp_log' ][ 'name_of_plugin' ][] = 'Some important event';`

Plugins must also add themselves to a list of enabled plugins. This will make it easier for blog owners to filter out which plugin they want to debug on the settings page. Add and edit the following code so it is executed when the plugin is loaded.

`$GLOBALS[ 'wp_log_plugins' ][] = 'name_of_plugin';`

A hypothetical example might be a plugin (let's call it "Big A") that uses the output buffer to change the letter "a" to "A". The events recorded by this might include the following:

`$GLOBALS[ 'wp_log' ][ 'big_a' ][] = 'Created output buffer';`
`$GLOBALS[ 'wp_log' ][ 'big_a' ][] = 'Output buffer callback';`
`$GLOBALS[ 'wp_log' ][ 'big_a' ][] = 'Replaced a with A in page';`

This plugin takes the "wp_log" array and dumps it to a file in the upload directory of the blog it's activated on. The file is linked from the plugin settings page and can be deleted on that page too. The file is a simple text file and is not protected in any way so be careful if auth cookies are logged by a plugin.

A "log" function isn't included but if you want to add one to your plugin check the constant WP_DEBUG_LOG is defined before adding to the log array. If your plugin doesn't use a class make sure that you call the log function a unique name so it doesn't conflict with other plugins.

`function log( $message ) {
	if ( defined( 'WP_DEBUG_LOG' ) )
		$GLOBALS[ 'wp_log' ][ 'name_of_plugin' ][] = $message;
}`

== Installation ==

1. Upload the `wp-debug-logger` folder to your `/wp-content/plugins/` directory

2. Activate the "WP Debug Logger" plugin in your WordPress administration interface

3. Go to Settings &rarr; WP Debug Logger as an Administrator to get started


== Changelog ==

= 0.1 =
Initial Release
