/**
 * Internal dependencies
 */
import { CAMPAIGN_ADS_MIGRATION, MONTH_IN_SECONDS } from '.~/constants';

/**
 * Returns the conversion campagin status
 *
 * @param {Object} adsCampaignConvertStatus wp option with the information related to the conversion status
 * @param {string} adsCampaignConvertStatus.status Conversion status: unconverted|converted|not-applicable|unknown
 * @param {number} adsCampaignConvertStatus.updated Unix time in seconds of the last time that the status was checked. Once is converted the timestamp is not updated anymore.
 * @param {number} timestamp Timestamp that is used as a reference
 * @return {string|null} return the conversion campaign status or null if it is not in the migration period
 */
const getConversionCampaignStatusNotice = (
	adsCampaignConvertStatus,
	timestamp = new Date().getTime()
) => {
	const isConverted = adsCampaignConvertStatus?.status === 'converted';
	const conversionTimestamp = adsCampaignConvertStatus?.updated;

	const isInMigrationPeriod =
		conversionTimestamp &&
		timestamp - conversionTimestamp * 1000 <
			CAMPAIGN_ADS_MIGRATION.MAXIMUM_MONTHS_TO_DISPLAY_NOTICE *
				MONTH_IN_SECONDS *
				1000;

	if ( ! isConverted ) return 'BEFORE_CONVERSION';
	else if ( isConverted && isInMigrationPeriod ) {
		return 'AFTER_CONVERSION';
	}
	return null;
};

export default getConversionCampaignStatusNotice;
