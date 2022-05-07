/**
 * Internal dependencies
 */
import { CAMPAIGN_TYPE } from '.~/constants';

/**
 * Checks if the Ads campaign has been converted
 *
 * @param {Object} adsCampaignConvertStatus wp option with the information related to the conversion status
 * @param {string} adsCampaignConvertStatus.status Conversion status: unconverted|converted|not-applicable|unknown
 * @param {number} adsCampaignConvertStatus.updated Unix time in seconds of the last time that the status was checked. Once is converted the timestamp is not updated anymore.
 * @param {string} campaignType The Ads campaign type
 * @return {boolean} return true if the Ads campaign has been converted to PMax otherwise false
 */
const isCampaignConverted = ( adsCampaignConvertStatus, campaignType ) => {
	return (
		adsCampaignConvertStatus?.status === 'converted' &&
		campaignType &&
		campaignType !== CAMPAIGN_TYPE.PERFORMANCE_MAX
	);
};

export default isCampaignConverted;
