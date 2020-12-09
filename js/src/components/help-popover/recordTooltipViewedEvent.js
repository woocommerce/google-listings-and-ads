/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';

const recordTooltipViewedEvent = ( id ) => {
	recordEvent( 'gla_tooltip_viewed', {
		id,
	} );
};

export default recordTooltipViewedEvent;
