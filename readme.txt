=== Nimble Links ===
Contributors: mattdaneshvar
Tags: short links, QR code, link shortener, nimble links
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generate short links and QR codes for your posts and pages via Nimble Links.

== Description ==

Nimble Links for WordPress connects to your Nimble Links account and lets you generate a short link and QR code for any post or page directly from the block editor.

**Features:**

* Generate a short link for any post or page with one click
* Get a matching QR code alongside every short link
* Copy the short link to your clipboard or open the link's manage page on Nimble Links
* Regenerate the short link at any time
* Secure — your API token is encrypted at rest and never exposed to the browser

**Third-Party Service: Nimble Links**

This plugin connects to the [Nimble Links](https://nimblelinks.com) API to create short links and QR codes. When you click "Generate Short Link" in the editor, the post title and URL are sent to the Nimble Links API. An API token is required to authenticate.

* Service website: [nimblelinks.com](https://nimblelinks.com)
* Terms of Use: [nimblelinks.com/terms](https://nimblelinks.com/terms)
* Privacy Policy: [nimblelinks.com/privacy](https://nimblelinks.com/privacy)

**Source Code**

The plugin's JavaScript is compiled with [@wordpress/scripts](https://www.npmjs.com/package/@wordpress/scripts). The unminified source and build tooling are available at https://github.com/nimblelinks/nimblelinks-for-wordpress.

**How it works:**

1. Install and activate the plugin
2. Go to Settings → Nimble Links and paste your API token
3. Open a post or page in the block editor
4. Open the Nimble Links sidebar and click "Generate Short Link"

== Installation ==

1. Upload the `nimble-links` folder to `/wp-content/plugins/`
2. Activate the plugin through the Plugins menu
3. Go to Settings → Nimble Links and connect your account

== Frequently Asked Questions ==

= Where do I get an API token? =

Log in to nimblelinks.com, go to Team Settings → API Tokens, and create a new token.

= What post types are supported? =

Posts and pages are supported out of the box.

= What data is sent to external servers? =

When you generate a short link, the post title and permalink are sent to the Nimble Links API (nimblelinks.com). QR code URLs are also retrieved from the API. No data is sent until you connect your account, and no visitor/analytics data is collected by this plugin.

= What happens if I deactivate the plugin? =

Your short links continue to work — they're managed by Nimble Links independently. The plugin data (post meta) is preserved so everything picks back up if you reactivate.

= What happens if I delete the plugin? =

The plugin removes its settings (API token, team name) but leaves post meta in place so your existing short links remain functional.

== Changelog ==

= 1.0.1 =
* Confirmed compatibility with WordPress 7.0.

= 1.0.0 =
* Initial release
