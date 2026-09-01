/**
 * External dependencies
 */
import { Notice } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './index.scss';

/**
 * Shared notice wrapper for shipping section notices.
 *
 * @param {Object} props
 * @param {JSX.Element} props.children Notice content.
 */
const ShippingInfoNotice = ( { children } ) => {
	return (
		<Notice
			className="gla-shipping-info-notice"
			isDismissible={ false }
			status="info"
		>
			{ children }
		</Notice>
	);
};

export default ShippingInfoNotice;
