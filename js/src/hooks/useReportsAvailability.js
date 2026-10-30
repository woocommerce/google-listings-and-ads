/**
 * Internal dependencies
 */
import { glaData } from '~/constants';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import canViewReports from '~/utils/canViewReports';

/**
 * @typedef {Object} ReportsAvailability
 * @property {boolean} hasMC Whether Merchant Center is set up or connected. Product Feed and Products reports need it.
 * @property {boolean} canViewReports Whether the Reports page can show any data. Ads-only merchants can still see their campaigns.
 */

/**
 * Tells which reports the merchant can view.
 *
 * @return {ReportsAvailability} The reports availability.
 */
const useReportsAvailability = () => {
	const { hasGoogleMCConnection } = useGoogleMCAccount();
	const hasMC = Boolean( glaData.mcSetupComplete || hasGoogleMCConnection );

	return {
		hasMC,
		canViewReports: canViewReports( {
			enableReports: glaData.enableReports,
			adsSetupComplete: glaData.adsSetupComplete,
			hasMC,
		} ),
	};
};

export default useReportsAvailability;
