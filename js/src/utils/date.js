/**
 * External dependencies
 */
import { format } from '@wordpress/date';

/**
 * Internal dependencies
 */
import { glaData } from '.~/constants';

/**
 * Format the date based on the WC Date format Settings being
 *
 * @param {Date|string} [date] The Date object to be formatted. Date object or string, parsable by moment.js.
 * In case date is empty, Date().now will be used by '@wordpress/date' format function.
 * @return {string} The formatted date
 */
export function formatDate( date ) {
	return format(
		glaData.dateFormat +
			( glaData.dateFormat.trim() && glaData.timeFormat.trim()
				? ', '
				: '' ) +
			glaData.timeFormat,
		date
	);
}
