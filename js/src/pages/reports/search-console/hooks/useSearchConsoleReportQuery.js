/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';
import { format } from '@wordpress/date';
import { getCurrentDates } from '@woocommerce/date';

/**
 * Internal dependencies
 */
import useUrlQuery from '~/hooks/useUrlQuery';
import useGoogleSearchConsoleAccount from '~/hooks/useGoogleSearchConsoleAccount';

export const DEFAULT_DATE_RANGE = 'period=month&compare=previous_year';

const toReportQuery = ( { after, before } ) => {
	return {
		after: format( 'Y-m-d', after ),
		before: format( 'Y-m-d', before ),
	};
};

/**
 * Get what every Search Console report needs before it can request its data: the connection
 * account, whether the report is available, and the date range selected in the URL.
 *
 * @return {{
 *   account: Object|null,
 *   isReportAvailable: boolean,
 *   hasFinishedAccount: boolean,
 *   primaryQuery: { after: string, before: string },
 *   secondaryQuery: { after: string, before: string },
 *   labels: { primary: string, secondary: string },
 *   days: number
 * }} The account, whether the report is available, whether the account has finished resolving,
 *   the `Y-m-d` date range of both periods, the labels of both periods, and the number of days
 *   the primary period covers.
 */
const useSearchConsoleReportQuery = () => {
	const urlQuery = useUrlQuery();
	const {
		account,
		isReportAvailable,
		hasFinishedResolution: hasFinishedAccount,
	} = useGoogleSearchConsoleAccount();

	const dates = useMemo( () => {
		const { primary, secondary } = getCurrentDates(
			urlQuery,
			DEFAULT_DATE_RANGE
		);

		return {
			primaryQuery: toReportQuery( primary ),
			secondaryQuery: toReportQuery( secondary ),
			labels: { primary: primary.label, secondary: secondary.label },
			days: primary.before.diff( primary.after, 'days' ) + 1,
		};
	}, [ urlQuery ] );

	return { account, isReportAvailable, hasFinishedAccount, ...dates };
};

export default useSearchConsoleReportQuery;
