/**
 * @typedef { import(".~/data/actions").CountryCode } CountryCode
 */

/**
 * @typedef {Object} CampaignFormValues
 * @property {Array<CountryCode>} countryCodes Selected country codes for the paid ads campaign.
 * @property {number} amount The daily average cost amount.
 */

/**
 * @typedef {Object} AssetGroupFormValues
 * @property {string | null} final_url - The page URL on merchant's website that people reach when they click the ad.
 * @property {string | null} business_name - The business name or brand of merchant's website.
 * @property {Array<string>} marketing_image - The URLs of the marketing images.
 * @property {Array<string>} square_marketing_image - The URLs of the square marketing images.
 * @property {Array<string>} logo - The URLs of the logos.
 * @property {Array<string>} headline - The headlines of the ad.
 * @property {Array<string>} long_headline - The long headlines of the ad.
 * @property {Array<string>} description - The descriptions of the ad.
 * @property {string | null} call_to_action_selection - The call to action selection aligning the goal of the ad.
 * @property {Array<string>} display_url_path - The path parts of the URL displayed on the ad.
 */

// This export is required for JSDoc in other files to import the type definitions from this file.
export default {};
