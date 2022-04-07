/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';

/**
 * Viewing tooltip
 *
 * @event gla_tooltip_viewed
 * @property {string} id (tooltip identifier)
 */
/**
 * @param {string} id The tooltip’s identifier
 * @fires gla_tooltip_viewed with the given `id`.
 */
const recordTooltipViewedEvent = ( id ) => {
	recordEvent( 'gla_tooltip_viewed', {
		id,
	} );
};

export default recordTooltipViewedEvent;
