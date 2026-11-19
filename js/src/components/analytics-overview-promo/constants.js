/**
 * Tracking context for events fired from the Analytics Overview promo.
 * Also used as the `referrer_id` when tagging this placement's CTA URLs, since
 * there is only ever one instance of this placement.
 */
export const ANALYTICS_OVERVIEW_PROMO_CONTEXT = 'analytics-overview-promo';

/**
 * The `@wordpress/preferences` key used to persist the promo's dismissed state.
 */
export const ANALYTICS_OVERVIEW_PROMO_DISMISSED_KEY =
	'gla_analytics_overview_promo_dismissed';
