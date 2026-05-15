/**
 * External dependencies
 */
import { Notice } from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import TrackableLink from '~/components/trackable-link';
import './index.scss';

/**
 * Shared notice component for shipping section notices.
 *
 * @param {Object} props
 * @param {string} props.message Translated string with a <link> placeholder, passed to createInterpolateElement.
 * @param {string} props.href URL for the TrackableLink.
 * @param {string} props.eventName Tracking event identifier.
 * @param {Object} [props.eventProps] Additional tracking properties.
 * @param {string} [props.className] Additional CSS class name.
 */
const ShippingInfoNotice = ( { message, href, eventName, eventProps, className } ) => {
	return (
		<Notice
			className={ classnames( 'gla-shipping-info-notice', className ) }
			isDismissible={ false }
			status="info"
		>
			{ createInterpolateElement( message, {
				link: (
					<TrackableLink
						target="_blank"
						type="external"
						href={ href }
						eventName={ eventName }
						eventProps={ eventProps }
					/>
				),
			} ) }
		</Notice>
	);
};

export default ShippingInfoNotice;
