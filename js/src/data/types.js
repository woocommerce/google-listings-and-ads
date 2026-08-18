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
 * @typedef {Object} GoogleSearchConsoleProperty
 * @property {string} url Property URL (domain or URL-prefix identifier).
 * @property {'domain'|'url_prefix'} type Property type.
 * @property {boolean} [selectable] Whether this property covers the store's domain and can be selected. Defaults to `true` when omitted.
 * @property {string} [reason] Explanation shown next to the property when `selectable` is `false`.
 */

/**
 * @typedef {Object} GoogleSearchConsoleAccount
 * @property {'connected'|'disconnected'|'incomplete'} status Connection status.
 * @property {'property_selection'|'verification'|'action_needed'|'reconnect'|'connection_failed'|'incomplete'} [step]
 *   Sub-state when `status` is `'incomplete'`.
 * @property {boolean} [skip_auth_prompt] Whether the Google auth prompt should be skipped because the merchant
 *   already has a Merchant Center connection. Always backend-supplied, never re-derived on the client.
 * @property {GoogleSearchConsoleProperty} [property] The resolved Google Search Console property, once selected or created.
 * @property {GoogleSearchConsoleProperty[]} [properties] Candidate properties to choose from when `step` is `'property_selection'`.
 * @property {boolean} [verified] Whether the resolved property has completed Google Search Console verification.
 * @property {boolean} [can_self_verify] Whether the merchant can self-verify via the single-click flow,
 *   or must be routed to Google's "request access" flow instead.
 * @property {string} [request_access_url] External URL to Google's "request access" flow when `can_self_verify` is `false`.
 */

// This export is required for JSDoc in other files to import the type definitions from this file.
export default {};
