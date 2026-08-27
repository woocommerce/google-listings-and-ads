/**
 * @typedef {import('~/data/actions').CountryCode} CountryCode
 */

/**
 * @typedef {Object} GeneralState
 * @property {string} version The version of this extension. Null if not yet connected.
 * @property {number | null} mcId The ID of the connected Google Merchant Center account. Null if not yet connected.
 * @property {number | null} adsId The ID of the connected Google Ads account. Null if not yet connected.
 */

/**
 * @typedef {Object} GoogleMCAccount
 * @property {number} id Account ID. It's 0 if not yet connected.
 * @property {string} status Connection status.
 * @property {null|'approved'|'disapproved'|'error'|'disabled'} wpcom_rest_api_status The status of Google WPCOM app's access to WooCommerce products, coupons, shipping and settings.
 */

/**
 * @typedef {Object} SuggestedAssets
 * @property {string} business_name The name of merchant's business or brand.
 * @property {string} final_url The page URL on merchant's website that people reach when they click the ad.
 * @property {string[]} headline The headlines for the ad.
 * @property {string[]} long_headline The headlines for the larger ad.
 * @property {string[]} description The descriptive text for the ad to provide additional context or details.
 * @property {string[]} marketing_image The URLs of the landscape images.
 * @property {string[]} square_marketing_image The URLs of the square images.
 * @property {string[]} portrait_marketing_image The URLs of the portrait images.
 * @property {string[]} logo The URLs of the logo images.
 * @property {string[]} display_url_path The path part of the display URL on the ad.
 * @property {string | null} call_to_action_selection The call-to-action text on the ad to let users know what the ad will get them to do. `null` if not selected.
 * @property {string[]} youtube_video The YouTube video IDs.
 */

/**
 * @typedef {Object} AssetEntity
 * @property {number} id The ID of the asset.
 * @property {string} content The content of the asset.
 */

/**
 * @typedef {Object} AssetsDictionary
 * @property {AssetEntity} [business_name] The name of merchant's business or brand.
 * @property {AssetEntity[]} [headline] The headlines for the ad.
 * @property {AssetEntity[]} [long_headline] The headlines for the larger ad.
 * @property {AssetEntity[]} [description] The descriptive text for the ad to provide additional context or details.
 * @property {AssetEntity[]} [marketing_image] The URLs of the landscape images.
 * @property {AssetEntity[]} [square_marketing_image] The URLs of the square images.
 * @property {AssetEntity[]} [portrait_marketing_image] The URLs of the portrait images.
 * @property {AssetEntity[]} [logo] The URLs of the logo images.
 * @property {AssetEntity} [call_to_action_selection] The call-to-action text on the ad to let users know what the ad will get them to do. `null` if not selected.
 * @property {AssetEntity} [youtube_video] The YouTube video ID.
 */

/**
 * @typedef {Object} AssetEntityGroup
 * @property {number} id The ID of the asset group.
 * @property {AssetsDictionary} assets The asset entities of the asset group.
 * @property {string} final_url The page URL on merchant's website that people reach when they click the ad.
 * @property {string[]} display_url_path The path part of the display URL on the ad.
 */

/**
 * @typedef {Object} AssetOperations
 * @property {number | null} id The ID of the asset. Set `null` to indicate the asset creation operation.
 * @property {string | null} content The content of the asset. Set `null` to indicate the asset deletion operation.
 * @property {string} field_type The enum field type of the asset.
 */

/**
 * @typedef {Object} AssetEntityGroupUpdateBody
 * @property {string} final_url The page URL on merchant's website that people reach when they click the ad.
 * @property {string} path1 The first path of the display URL on the ad.
 * @property {string} path2 The second path of the display URL on the ad.
 * @property {AssetOperations[]} assets The creation and deletion operations for updating the asset group.
 */

/**
 * @typedef {Object} AdsBudgetMetricsEntity
 * @property {number} conversions The estimated number of conversions (unit sales) for a typical week.
 * @property {number} conversionsValue The estimated total value of all the conversions (sales volume) the campaign will generate in a week.
 * @property {number} cost The estimated average amount will be spent weekly during a month.
 */

/**
 * @typedef {Object} AdsBudgetRecommendationEntity
 * @property {string} currency The currency to use for the recommendation.
 * @property {CountryCode} country The country code of the recommendation.
 * @property {number} dailyBudget The recommended daily budget for a country.
 * @property {AdsBudgetMetricsEntity} [metrics] The estimated metrics for the campaign.
 */

/**
 * @typedef {Object} AdsBudgetRecommendation
 * @property {AdsBudgetRecommendationEntity} recommended The recommended budget.
 * @property {AdsBudgetRecommendationEntity} [high] The high budget recommendation.
 * @property {AdsBudgetRecommendationEntity} [low] The low budget recommendation.
 * @property {number} recommendedDailyBudget The recommended daily budget.
 * @property {number} dailyBudgetBaseline The daily budget baseline.
 * @property {Object} eventProps The relevant event properties.
 */

/**
 * @typedef {Object} AdsBudgetMetrics
 * @property {string} currency The currency to use for the metrics.
 * @property {CountryCode} country The country code of the metrics.
 * @property {number} dailyBudget The daily budget it queried metrics for.
 * @property {AdsBudgetMetricsEntity} [metrics] The estimated metrics for the budget.
 */

/**
 * @typedef {Object} AdsIncentiveCredits
 * @property {string} adsCurrency The currency of the current connected Google Ads account.
 * @property {string} currency The currency of the credits.
 * @property {string} country The country code of the credits.
 * @property {number} spending The minimum spending required to be eligible for the credits.
 * @property {number} credit The credits will be given back.
 */

/**
 * @typedef {Object} GoogleTagManagerAccountRef
 * @property {string} id Account ID.
 * @property {string} name Account name.
 * @property {string} [tagManagerUrl] Ready-made URL to open this account in Google Tag Manager.
 */

/**
 * @typedef {Object} GoogleTagManagerContainerRef
 * @property {string} id Internal container ID, used to select/identify the container.
 * @property {string} publicId Merchant-facing container ID (`GTM-XXXXXXX` format) — this is what
 *   the UI displays, not `id`.
 * @property {string} name Container name.
 * @property {string} [tagManagerUrl] Ready-made URL to open this container in Google Tag Manager.
 */

/**
 * @typedef {Object} GoogleTagManagerAccount
 * @property {'connected'|'disconnected'|'incomplete'} status Connection status — matches
 *   `GOOGLE_TAG_MANAGER_ACCOUNT_STATUS` exactly.
 * @property {'no_account'|'account_selection'|'container_selection'} [step] Which not-yet-connected
 *   step the merchant is on — matches `GOOGLE_TAG_MANAGER_STEP` exactly. Only meaningful while
 *   `status` is `incomplete`.
 * @property {string} [id] The selected account's ID, once one has been chosen (present from the
 *   `container_selection` step onward).
 * @property {string} [containerId] The selected container's ID, present only once `status` is
 *   `connected`.
 */

// This export is required for JSDoc in other files to import the type definitions from this file.
export default {};
