# Nimble Links for WordPress

A WordPress plugin that connects to [Nimble Links](https://nimblelinks.com) and lets you generate short links and QR codes for any post or page directly from the block editor.

- Generate a short link and QR code from the block editor sidebar
- Copy the short link, open its manage page, or regenerate it at any time
- Works for posts and pages
- API token is encrypted at rest and never exposed to the browser

## Requirements

- WordPress 6.0+
- PHP 7.4+
- A [Nimble Links](https://nimblelinks.com) account and API token

## Installation

Grab the latest release zip from the [releases page](../../releases) and upload it via **Plugins → Add New → Upload Plugin**, or install from the WordPress.org plugin directory once published.

After activating, go to **Settings → Nimble Links** and paste your API token.

## Development

```bash
# Install dependencies
composer install
npm install

# Build JS assets
npm run build

# Watch mode
npm run start

# Run tests
vendor/bin/phpunit
```

### Building a distribution zip

```bash
./bin/build-zip.sh
```

This produces `dist/nimble-links.zip` with production dependencies and compiled assets only.

## License

[GPLv2 or later](LICENSE.md).
