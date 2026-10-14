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
 * @typedef {import('~/data/selectors').SearchConsoleQuery} SearchConsoleQuery
 */

/**
 * Get the Search Console top search queries of the date range selected in the URL.
 *
 * Nothing is requested until Search Console is connected, as the report endpoint only
 * answers for a connected, verified property.
 *
 * @return {{
 *   loaded: boolean,
 *   property: string|undefined,
 *   queries: Array<SearchConsoleQuery>,
 *   limit: number|undefined,
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
				return { loaded: hasFinishedAccount, data: null };
			}

			const selector = select( STORE_KEY );

			return {
				loaded: selector.hasFinishedResolution( selectorName, [
					reportQuery,
				] ),
				data: selector[ selectorName ]( reportQuery ),
			};
		},
		[ isConnected, hasFinishedAccount, reportQuery ]
	);

	return {
		loaded: report.loaded,
		property: account?.site_url,
		queries: report.data?.queries ?? emptyQueries,
		limit: report.data?.limit,
		days,
	};
};

export default useTopSearchQueries;
