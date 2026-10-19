/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';
import { GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS, glaData } from '~/constants';

/**
 * Whether the Organic search report is available, i.e. Search Console is connected.
 *
 * Uses the connection status already in the store when there is one, so connecting or
 * disconnecting earlier on the same page is reflected straight away. Falls back to the stored
 * connection data sent with the page when there is no status yet, or when the last status check
 * failed transiently, so a momentary outage doesn't hide the report. It never requests the
 * connection status itself.
 *
 * @return {boolean} Whether the Organic search report is available.
 */
const useIsOrganicSearchReportAvailable = () => {
	const status = useSelect( ( select ) => {
		return select( STORE_KEY ).getLoadedGoogleSearchConsoleStatus();
	}, [] );

	if (
		status === null ||
		status === GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS.TRANSIENT_ERROR
	) {
		return Boolean( glaData.searchConsoleConnected );
	}

	return status === GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS.CONNECTED;
};

export default useIsOrganicSearchReportAvailable;
