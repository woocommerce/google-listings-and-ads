/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './index.scss';

const DEFAULT_IFRAME_PROPS = {
	allow: 'fullscreen',
	allowtransparency: 'true',
	scrolling: 'no',
	width: '100%',
	height: '100%',
};

const WistiaVideo = ( props ) => {
	const { src, title, iframeProps = {} } = props;

	const getIframeTitle = () => {
		if ( ! title ) {
			return __( 'Wistia Video', 'google-listings-and-ads' );
		}
		return sprintf(
			// translators: %s: The title of the iframe.
			__( '%s Video', 'google-listings-and-ads' ),
			title
		);
	};

	return (
		<div className="wistia-responsive-padding">
			<div className="wistia-responsive-wrapper">
				<iframe
					src={ src }
					title={ getIframeTitle() }
					{ ...{ ...DEFAULT_IFRAME_PROPS, ...iframeProps } }
				/>
			</div>
		</div>
	);
};

export default WistiaVideo;
