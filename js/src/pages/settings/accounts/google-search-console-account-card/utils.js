/**
 * Builds the outbound link to a property in Google Search Console itself.
 *
 * @param {string} siteUrl The property's raw Sites API identifier.
 * @return {string} The Google Search Console URL for that property.
 */
export const getSearchConsolePropertyUrl = ( siteUrl ) =>
	`https://search.google.com/search-console?resource_id=${ encodeURIComponent(
		siteUrl
	) }`;

/**
 * Builds the outbound link to the Performance > Search results report for a property in Google
 * Search Console itself.
 *
 * @param {string} siteUrl The property's raw Sites API identifier.
 * @return {string} The Google Search Console Performance report URL for that property.
 */
export const getSearchConsolePerformanceReportUrl = ( siteUrl ) =>
	`https://search.google.com/search-console/performance/search-analytics?resource_id=${ encodeURIComponent(
		siteUrl
	) }`;

/**
 * Wraps a destination URL in Google's own account-selection redirect, so the link resolves
 * under a specific Google account.
 *
 * @param {string} destinationUrl The URL to continue to once an account is resolved.
 * @param {string} email The Google account email to resolve to.
 * @return {string} The wrapped, account-aware URL.
 */
export const getAccountAwareUrl = ( destinationUrl, email ) =>
	`https://accounts.google.com/accountchooser?continue=${ encodeURIComponent(
		destinationUrl
	) }&Email=${ encodeURIComponent( email ) }`;
