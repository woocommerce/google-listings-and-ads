/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { format } from '@wordpress/date';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';
import { REPORT_SOURCE_PAID } from '~/constants';
import useGoogleAdsAccount from '~/hooks/useGoogleAdsAccount';

/**
 * Returns the ISO date string for `daysAgo` days before today.
 *
 * @param {number} daysAgo Number of days to subtract from today.
 * @return {string} Date string in 'Y-m-d' format.
 */
function getDateDaysAgo( daysAgo ) {
	const date = new Date();
	date.setDate( date.getDate() - daysAgo );
	return format( 'Y-m-d', date );
}

/**
 * @typedef {Object} HasRecentAdSpendPayload
 * @property {boolean} hasFinishedResolution Whether the resolution has completed.
 * @property {boolean} hasAdSpend Whether there has been any Google Ads spend within the past N days.
 */

/**
 * Hook that checks whether there has been any Google Ads spend within the past N days.
 *
 * @param {number} [days=14] Number of days to look back for ad spend. Defaults to 14.
 * @return {HasRecentAdSpendPayload} Resolution state, and whether ad spend exists.
 */
const useHasRecentAdSpend = ( days = 14 ) => {
	const {
		hasGoogleAdsConnection,
		hasFinishedResolution: hasResolvedGoogleAdsAccount,
	} = useGoogleAdsAccount();

	return useSelect(
		( select ) => {
			if ( ! hasGoogleAdsConnection ) {
				return {
					hasFinishedResolution: hasResolvedGoogleAdsAccount,
					hasAdSpend: false,
				};
			}

			const selector = select( STORE_KEY );
			const args = [
				'programs',
				REPORT_SOURCE_PAID,
				{
					after: getDateDaysAgo( days ),
					before: getDateDaysAgo( 0 ),
					fields: [ 'spend' ],
				},
			];
			const report = selector.getReportByApiQuery( ...args );
			const hasFinishedResolution = selector.hasFinishedResolution(
				'getReportByApiQuery',
				args
			);

			return {
				hasFinishedResolution,
				hasAdSpend: report?.totals?.spend > 0,
			};
		},
		[ days, hasGoogleAdsConnection, hasResolvedGoogleAdsAccount ]
	);
};

export default useHasRecentAdSpend;
