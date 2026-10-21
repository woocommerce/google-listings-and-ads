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

/**
 * Get a Search Console report of the date range selected in the URL, once Search Console is connected.
 *
 * @param {string} selectorName Name of the store selector that returns the report of a date range.
 * @return {{
 *   hasFinishedResolution: boolean,
 *   property: string|null,
 *   report: Object|null,
 *   days: number
 * }} The report, the connected property and the number of days the date range covers.
 */
const useSearchConsoleReport = ( selectorName ) => {
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

	const { hasFinishedResolution, report } = useSelect(
		( select ) => {
			if ( ! isConnected ) {
				return {
					hasFinishedResolution: hasFinishedAccount,
					report: null,
				};
			}

			const selector = select( STORE_KEY );

			return {
				hasFinishedResolution: selector.hasFinishedResolution(
					selectorName,
					[ reportQuery ]
				),
				report: selector[ selectorName ]( reportQuery ),
			};
		},
		[ isConnected, hasFinishedAccount, selectorName, reportQuery ]
	);

	return {
		hasFinishedResolution,
		property: account?.site_url ?? null,
		report,
		days,
	};
};

export default useSearchConsoleReport;
