/**
 * Internal dependencies
 */
import { glaData } from '~/constants';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';

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
	const canViewReports = Boolean(
		glaData.enableReports && ( hasMC || glaData.adsSetupComplete )
	);

	return { hasMC, canViewReports };
};

export default useReportsAvailability;
