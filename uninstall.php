<?php

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('nimble_links_api_token');
delete_option('nimble_links_team_name');
