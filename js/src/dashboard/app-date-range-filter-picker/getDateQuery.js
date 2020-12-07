/**
 * External dependencies
 */
import { getQuery } from '@woocommerce/navigation';
import { getDateParamsFromQuery, getCurrentDates } from '@woocommerce/date';

const getDateQuery = () => {
	const query = getQuery();
	const { period, compare, before, after } = getDateParamsFromQuery( query );
	const { primary: primaryDate, secondary: secondaryDate } = getCurrentDates(
		query
	);
	return {
		period,
		compare,
		before,
		after,
		primaryDate,
		secondaryDate,
	};
};

export default getDateQuery;
