/**
 * External dependencies
 */
import { Link } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { recordGlaEvent } from '.~/utils/tracks';

/**
 * A {@link module:@woocommerce/components~Link} component that will call `recordGlaEvent` with `eventName` and `eventProps` parameters upon click.
 *
 * @param {Object} props Props to be forwarded to {@link module:@woocommerce/components~Link}.
 * @param {string} props.eventName The eventName used in calling `recordGlaEvent`.
 * @param {Object} [props.eventProps] Event properties to include in the event.
 */
const TrackableLink = ( props ) => {
	const { eventName, eventProps, onClick = () => {}, ...rest } = props;

	const handleClick = ( e ) => {
		if ( eventName ) {
			recordGlaEvent( eventName, eventProps );
		}

		onClick( e );
	};

	return <Link { ...rest } onClick={ handleClick }></Link>;
};

export default TrackableLink;
