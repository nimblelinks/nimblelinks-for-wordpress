import { registerPlugin } from '@wordpress/plugins';
import { link as linkIcon } from '@wordpress/icons';
import Sidebar from './components/Sidebar';

registerPlugin('nimble-links', {
    render: Sidebar,
    icon: linkIcon,
});
