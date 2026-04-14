import { PluginSidebar } from '@wordpress/editor';
import { useSelect } from '@wordpress/data';
import { useState, useEffect } from '@wordpress/element';
import { Spinner, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import ShortLink from './ShortLink';
import QrCode from './QrCode';

const SUPPORTED_POST_TYPES = ['post', 'page'];

export default function Sidebar() {
    const postId = useSelect((select) => select('core/editor').getCurrentPostId(), []);
    const postType = useSelect((select) => select('core/editor').getCurrentPostType(), []);

    const [linkData, setLinkData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [creating, setCreating] = useState(false);
    const [error, setError] = useState('');

    const isSupported = SUPPORTED_POST_TYPES.includes(postType);

    useEffect(() => {
        if (!postId || !isSupported || !window.nimbleLinks?.isConnected) {
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
    }, [postId, isSupported]);

    const handleCreate = (isRegenerate = false) => {
        if (isRegenerate && !window.confirm(__('Generate a new short link? The existing one will be replaced.', 'nimble-links'))) {
            return;
        }

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
        if (!isSupported) {
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
                    <div style={{ marginTop: '16px' }}>
                        <h3 style={{ marginTop: 0, marginBottom: '8px' }}>{__('Actions', 'nimble-links')}</h3>
                        <div style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}>
                            <Button
                                variant="secondary"
                                onClick={() => handleCreate(true)}
                                isBusy={creating}
                                disabled={creating}
                                __next40pxDefaultSize
                            >
                                {creating ? __('Regenerating…', 'nimble-links') : __('Regenerate Short Link', 'nimble-links')}
                            </Button>
                            {linkData.manage_url && (
                                <Button
                                    variant="secondary"
                                    href={linkData.manage_url}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    __next40pxDefaultSize
                                >
                                    {__('Manage on Nimble Links', 'nimble-links')}
                                </Button>
                            )}
                        </div>
                        {error && <p style={{ color: '#d63638', marginTop: '8px' }}>{error}</p>}
                    </div>
                </div>
            );
        }

        return (
            <div style={{ padding: '16px' }}>
                <Button variant="primary" onClick={() => handleCreate(false)} isBusy={creating} disabled={creating}>
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
