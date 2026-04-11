=== Nimble Links ===
Contributors: nimblelinks
Tags: short links, QR code, link shortener, nimble links
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically create short links and QR codes for your posts via Nimble Links.

== Description ==

Nimble Links for WordPress connects to your Nimble Links account and automatically generates short links and QR codes every time you publish a post.

**Features:**

* Automatically create a short link when a post is published
* Automatically generate a QR code for each short link
* Gutenberg sidebar panel with copy-to-clipboard short link and downloadable QR code
* Secure — your API token is encrypted at rest and never exposed to the browser

**Third-Party Service: Nimble Links**

This plugin connects to the [Nimble Links](https://nimblelinks.com) API to create short links and QR codes. When you publish a post (or click "Generate Short Link" in the editor), the post title and URL are sent to the Nimble Links API. An API token is required to authenticate.

* Service website: [nimblelinks.com](https://nimblelinks.com)
* Terms of Use: [nimblelinks.com/terms](https://nimblelinks.com/terms)
* Privacy Policy: [nimblelinks.com/privacy](https://nimblelinks.com/privacy)

**How it works:**

1. Install and activate the plugin
2. Go to Settings → Nimble Links and paste your API token
3. Publish a post — the short link and QR code are created automatically
4. Open the Nimble Links sidebar in the block editor to copy the link or download the QR code

== Installation ==

1. Upload the `nimble-links` folder to `/wp-content/plugins/`
2. Activate the plugin through the Plugins menu
3. Go to Settings → Nimble Links and connect your account

== Frequently Asked Questions ==

= Where do I get an API token? =

Log in to nimblelinks.com, go to Team Settings → API Tokens, and create a new token.

= What post types are supported? =

Version 1.0 supports the default `post` type. Additional post types will be supported in a future release.

= What data is sent to external servers? =

When a short link is created, the post title and permalink are sent to the Nimble Links API (nimblelinks.com) to generate the short link. QR code URLs are also retrieved from the API. No data is sent until you connect your account, and no visitor/analytics data is collected by this plugin.

= What happens if I deactivate the plugin? =

Your short links continue to work — they're managed by Nimble Links independently. The plugin data (post meta) is preserved so everything picks back up if you reactivate.

= What happens if I delete the plugin? =

The plugin removes its settings (API token, preferences) but leaves post meta in place so your existing short links remain functional.

== Changelog ==

= 1.0.0 =
* Initial release
