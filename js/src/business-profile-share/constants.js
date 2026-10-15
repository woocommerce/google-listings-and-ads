/**
 * Internal dependencies
 */
import { glaData } from '~/constants';

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
 * The longest text Google Business Profile accepts, in characters, as set on the server.
 */
export const SUMMARY_MAX_LENGTH = glaData.businessProfileSummaryMaxLength;

/**
 * Tracking context for the block editor's Google Business Profile panel.
 */
export const BUSINESS_PROFILE_SHARE_PANEL_CONTEXT =
	'business-profile-share-panel';

/**
 * Tracking context for the Classic Editor's Google Business Profile meta box.
 */
export const BUSINESS_PROFILE_SHARE_META_BOX_CONTEXT =
	'business-profile-share-meta-box';

/**
 * The status of a post that has never been saved.
 */
export const NEVER_SAVED_POST_STATUS = 'auto-draft';
