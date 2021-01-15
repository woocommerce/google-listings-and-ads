/**
 * External dependencies
 */
import { Link } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import recordEvent from '../../utils/recordEvent';

/**
 * A `@woocommerce/components` `Link` component that will call `recordEvent` with `eventName` and `eventProps` parameters upon click.
 *
 * @param {Object} props Props to be forwarded to {@link Link}.
 * @param {string} props.eventName The eventName used in calling `recordEvent`.
 * @param {Object} [props.eventProps] Event properties to include in the event.
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

export default TrackableLink;
