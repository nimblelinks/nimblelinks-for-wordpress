<?php

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('nimble_links_api_token');
delete_option('nimble_links_team_name');
delete_option('nimble_links_auto_short_link');
delete_option('nimble_links_auto_qr_code');
