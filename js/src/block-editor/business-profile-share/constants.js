/**
 * Post meta key holding whether the post is shared to Google Business Profile when published.
 */
export const SHARE_META_KEY = '_wc_gla_gbp_share';

/**
 * Post meta key holding the text sent to Google Business Profile.
 */
export const SUMMARY_META_KEY = '_wc_gla_gbp_summary';

/**
 * Post REST field reporting whether the post has been shared, and its link once Google provides one.
 */
export const SHARE_STATUS_FIELD = 'gla_gbp_share_status';

/**
 * Post REST field holding the outcome of the last attempt to share or remove the post, with a
 * ready-to-display message and status for a non-success outcome.
 */
export const SYNC_OUTCOME_FIELD = 'gla_gbp_sync_outcome';

/**
 * Tracking context for the block editor's Google Business Profile panel.
 */
export const BUSINESS_PROFILE_SHARE_PANEL_CONTEXT =
	'business-profile-share-panel';
