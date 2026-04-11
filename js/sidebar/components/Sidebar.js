import { PluginSidebar } from '@wordpress/editor';
import { useSelect } from '@wordpress/data';
import { useState, useEffect } from '@wordpress/element';
import { Spinner, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import ShortLink from './ShortLink';
import QrCode from './QrCode';

export default function Sidebar() {
    const postId = useSelect((select) => select('core/editor').getCurrentPostId(), []);
    const postType = useSelect((select) => select('core/editor').getCurrentPostType(), []);

    const [linkData, setLinkData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [creating, setCreating] = useState(false);
    const [error, setError] = useState('');

    useEffect(() => {
        if (!postId || postType !== 'post' || !window.nimbleLinks?.isConnected) {
            setLoading(false);
            return;
        }

        apiFetch({ path: `${window.nimbleLinks.restUrl}/links/${postId}` })
            .then((data) => {
                setLinkData(data);
            })
            .catch(() => {
                setLinkData(null);
            })
            .finally(() => {
                setLoading(false);
            });
    }, [postId, postType]);

    const handleCreate = () => {
        setCreating(true);
        setError('');

        apiFetch({
            path: `${window.nimbleLinks.restUrl}/links`,
            method: 'POST',
            data: { post_id: postId },
        })
            .then((data) => {
                setLinkData(data);
            })
            .catch((err) => {
                setError(err.message || __('Failed to create short link.', 'nimble-links'));
            })
            .finally(() => {
                setCreating(false);
            });
    };

    const renderContent = () => {
        if (postType !== 'post') {
            return null;
        }

        if (!window.nimbleLinks?.isConnected) {
            return (
                <div style={{ padding: '16px' }}>
                    <p>{__('Connect your Nimble Links account to generate short links and QR codes.', 'nimble-links')}</p>
                    <Button variant="link" href={window.nimbleLinks.settingsUrl}>
                        {__('Go to Settings', 'nimble-links')}
                    </Button>
                </div>
            );
        }

        if (loading) {
            return (
                <div style={{ padding: '16px', textAlign: 'center' }}>
                    <Spinner />
                </div>
            );
        }

        if (linkData?.url) {
            return (
                <div style={{ padding: '16px' }}>
                    <ShortLink url={linkData.url} />
                    {(linkData.qr_svg || linkData.qr_png) && (
                        <QrCode svg={linkData.qr_svg} png={linkData.qr_png} />
                    )}
                </div>
            );
        }

        return (
            <div style={{ padding: '16px' }}>
                <Button variant="primary" onClick={handleCreate} isBusy={creating} disabled={creating}>
                    {creating ? __('Creating…', 'nimble-links') : __('Generate Short Link', 'nimble-links')}
                </Button>
                {error && <p style={{ color: '#d63638', marginTop: '8px' }}>{error}</p>}
            </div>
        );
    };

    return (
        <PluginSidebar
            name="nimble-links"
            title={__('Nimble Links', 'nimble-links')}
        >
            {renderContent()}
        </PluginSidebar>
    );
}
