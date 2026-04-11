import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

function downloadUrl(url, filename) {
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
}

export default function QrCode({ svg, png }) {
    return (
        <div>
            <h3 style={{ marginBottom: '8px' }}>{__('QR Code', 'nimble-links')}</h3>
            {svg && (
                <img
                    src={svg}
                    alt={__('QR Code', 'nimble-links')}
                    style={{ width: '100%', maxWidth: '200px', marginBottom: '12px', display: 'block' }}
                />
            )}
            <div style={{ display: 'flex', gap: '8px' }}>
                {png && (
                    <Button variant="secondary" onClick={() => downloadUrl(png, 'qr-code.png')}>
                        {__('Download PNG', 'nimble-links')}
                    </Button>
                )}
                {svg && (
                    <Button variant="secondary" onClick={() => downloadUrl(svg, 'qr-code.svg')}>
                        {__('Download SVG', 'nimble-links')}
                    </Button>
                )}
            </div>
        </div>
    );
}
