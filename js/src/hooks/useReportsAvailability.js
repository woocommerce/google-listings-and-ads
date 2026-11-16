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
 * @property {boolean} hasFinishedResolution Whether `hasMC` is final. It is false while the Merchant Center account is still loading.
 */

/**
 * Tells which reports the merchant can view.
 *
 * @return {ReportsAvailability} The reports availability.
 */
const useReportsAvailability = () => {
	const { hasGoogleMCConnection, hasFinishedResolution } =
		useGoogleMCAccount();
	const hasMC = Boolean( glaData.mcSetupComplete || hasGoogleMCConnection );

	return {
		hasMC,
		canViewReports: canViewReports( {
			enableReports: glaData.enableReports,
			adsSetupComplete: glaData.adsSetupComplete,
			hasMC,
		} ),
		// Completed setup already settles `hasMC`, so there is no need to wait for the account.
		hasFinishedResolution: Boolean(
			glaData.mcSetupComplete || hasFinishedResolution
		),
	};
};

export default useReportsAvailability;
