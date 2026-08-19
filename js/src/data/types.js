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
 * A candidate Search Console property, as returned by `GET search-console/connection`'s
 * `matches` field — a raw Sites API `siteEntry` plus two backend-computed booleans. Only
 * appears on a genuine multi-match the merchant must resolve themselves.
 *
 * @typedef {Object} GoogleSearchConsoleMatch
 * @property {string} siteUrl Raw Sites API property identifier (a full URL-prefix, or an `sc-domain:` domain property).
 * @property {string} permissionLevel Raw Sites API permission enum (e.g. `siteOwner`, `siteFullUser`,
 *   `siteUnverifiedUser`). Never `siteRestrictedUser` — those properties are excluded entirely upstream.
 * @property {boolean} covers Whether this property covers the store's specific URL, not just its domain.
 * @property {boolean} usable Whether this property can be selected. There is no `reason` field — derive
 *   explanatory copy for `usable: false` client-side from `covers`/`permissionLevel`.
 */

/**
 * @typedef {Object} GoogleSearchConsoleAccount
 * @property {'connected'|'disconnected'|'incomplete'|'action-needed'|'reconnect'|'connection-failed'|'transient-error'} status
 *   Connection status — a single flat enum, matching the backend's `Connection::STATE_*` values exactly.
 * @property {GoogleSearchConsoleMatch[]} [matches] Candidate properties the merchant must choose between.
 *   Present only on a genuine multi-match — absent (not an empty array) whenever the backend already
 *   auto-resolved a single match or silently created one.
 * @property {string} [site_url] The connected property's raw Sites API identifier, only present when
 *   `status` is `'connected'`. Proposed backend addition — not yet sent by the real backend.
 * @property {boolean} [just_resolved] Whether this exact call is the one where a property was just
 *   auto-resolved and verified with no merchant action — present only on that one transitioning call,
 *   never on any call after. Proposed backend addition — not yet sent by the real backend.
 */

// This export is required for JSDoc in other files to import the type definitions from this file.
export default {};
