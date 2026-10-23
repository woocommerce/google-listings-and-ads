/**
 * Internal dependencies
 */
import { glaData } from '~/constants';

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
