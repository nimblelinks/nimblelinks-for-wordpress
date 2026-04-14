import { useState } from '@wordpress/element';
import { Button, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { copy } from '@wordpress/icons';

export default function ShortLink({ url }) {
    const [copied, setCopied] = useState(false);

    const handleCopy = () => {
        navigator.clipboard.writeText(url).then(() => {
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        });
    };

    return (
        <div style={{ marginBottom: '16px' }}>
            <h3 style={{ marginBottom: '8px' }}>{__('Short Link', 'nimble-links')}</h3>
            <div style={{ display: 'flex', gap: '8px', alignItems: 'stretch' }}>
                <div style={{ flex: 1 }}>
                    <TextControl
                        value={url}
                        readOnly
                        onChange={() => {}}
                        __next40pxDefaultSize
                        __nextHasNoMarginBottom
                    />
                </div>
                <Button
                    icon={copy}
                    label={__('Copy', 'nimble-links')}
                    onClick={handleCopy}
                    variant="secondary"
                    __next40pxDefaultSize
                >
                    {copied ? __('Copied!', 'nimble-links') : __('Copy', 'nimble-links')}
                </Button>
            </div>
        </div>
    );
}
