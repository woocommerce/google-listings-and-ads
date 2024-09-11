/**
 * @typedef { import(".~/data/actions").CountryCode } CountryCode
 *
 * @typedef {Object} CampaignData
 * @property {number|undefined} amount Daily average cost of the paid ads campaign.
 * @property {Array<CountryCode>} countryCodes Audience country codes of the paid ads campaign. Example: 'US'.
 */

const KEY_PAID_ADS = 'gla-onboarding-paid-ads';

const { sessionStorage } = window;

const clientSession = {
	/**
	 * @param {CampaignData} data Campaign data to be stored.
	 * @param {number|undefined} data.amount Daily average cost of the campaign.
	 * @param {Array<CountryCode>} data.countryCodes Country codes of the campaign.
	 */
	setCampaign( { amount, countryCodes } ) {
		const json = JSON.stringify( { amount, countryCodes } );
		sessionStorage.setItem( KEY_PAID_ADS, json );
	},

	/**
	 * @return {CampaignData|null} The stored campaign data. It will return `null` if stored data is not available.
	 */
	getCampaign() {
		const json = sessionStorage.getItem( KEY_PAID_ADS );
		return json === null ? null : JSON.parse( json );
	},
};

export default clientSession;
