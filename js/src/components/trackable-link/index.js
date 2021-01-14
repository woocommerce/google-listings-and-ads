/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { Link } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import recordEvent from '../../utils/recordEvent';

/**
 * A component that wraps around `@woocommerce/components` `Link` component.
 * Upon clicking on the link, it will call `recordEvent` with `eventName` and `eventProps` parameters.
 *
 * @param {Object} props All the Link props, plus `eventName` and `eventProps`.
 */
const TrackableLink = ( props ) => {
	const { eventName, eventProps, onClick = () => {}, ...rest } = props;

	const handleClick = () => {
		if ( eventName ) {
			recordEvent( eventName, eventProps );
		}

		onClick();
	};

	return <Link { ...rest } onClick={ handleClick }></Link>;
};

TrackableLink.propTypes = {
	/**
	 * The eventName used in calling `recordEvent`.
	 */
	eventName: PropTypes.string.isRequired,

	/**
	 * Event properties to include in the event.
	 */
	eventProps: PropTypes.object,
};

export default TrackableLink;
