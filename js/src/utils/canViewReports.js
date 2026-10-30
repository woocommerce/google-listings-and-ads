/**
 * Whether the Reports page can show any data. Ads-only merchants can still see their campaigns.
 *
 * @param {Object} args
 * @param {boolean} args.enableReports Whether reports are enabled.
 * @param {boolean} args.adsSetupComplete Whether Google Ads setup is complete.
 * @param {boolean} args.hasMC Whether Merchant Center is set up or connected.
 * @return {boolean} Whether the merchant can view reports.
 */
const canViewReports = ( { enableReports, adsSetupComplete, hasMC } ) => {
	return Boolean( enableReports && ( hasMC || adsSetupComplete ) );
};

export default canViewReports;
