/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { format } from '@wordpress/date';
import { getCurrentDates } from '@woocommerce/date';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';
import useUrlQuery from '~/hooks/useUrlQuery';
import useGoogleSearchConsoleAccount from '~/hooks/useGoogleSearchConsoleAccount';

const selectorName = 'getSearchConsoleQueriesReport';
const emptyQueries = [];

/**
 * @typedef {import('~/data/types.js').GoogleSearchConsoleQuery} GoogleSearchConsoleQuery
 */

/**
 * Get the Search Console top search queries of the date range selected in the URL.
 *
 * @return {{
 *   hasFinishedResolution: boolean,
 *   property: string|null,
 *   queries: Array<GoogleSearchConsoleQuery>,
 *   limit: number|null,
 *   days: number
 * }} The report, the connected property and the number of days the date range covers.
 */
const useTopSearchQueries = () => {
	const urlQuery = useUrlQuery();
	const {
		account,
		isConnected,
		hasFinishedResolution: hasFinishedAccount,
	} = useGoogleSearchConsoleAccount();

	const { reportQuery, days } = useMemo( () => {
		const { after, before } = getCurrentDates( urlQuery ).primary;

		return {
			reportQuery: {
				after: format( 'Y-m-d', after ),
				before: format( 'Y-m-d', before ),
			},
			days: before.diff( after, 'days' ) + 1,
		};
	}, [ urlQuery ] );

	const report = useSelect(
		( select ) => {
			if ( ! isConnected ) {
				return {
					hasFinishedResolution: hasFinishedAccount,
					data: null,
				};
			}

			const selector = select( STORE_KEY );

			return {
				hasFinishedResolution: selector.hasFinishedResolution(
					selectorName,
					[ reportQuery ]
				),
				data: selector[ selectorName ]( reportQuery ),
			};
		},
		[ isConnected, hasFinishedAccount, reportQuery ]
	);

	return {
		hasFinishedResolution: report.hasFinishedResolution,
		property: account?.site_url ?? null,
		queries: report.data?.queries ?? emptyQueries,
		limit: report.data?.limit ?? null,
		days,
	};
};

export default useTopSearchQueries;
