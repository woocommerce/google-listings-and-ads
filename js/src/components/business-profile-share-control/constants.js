/**
 * Internal dependencies
 */
import { glaBusinessProfileData } from '~/constants';

/**
 * The longest text Google Business Profile accepts, in characters, as set on the server.
 */
export const SUMMARY_MAX_LENGTH =
	glaBusinessProfileData.businessProfileSummaryMaxLength;

/**
 * Post REST field reporting whether the post has been shared, and its link once Google provides one.
 */
export const SHARE_STATUS_FIELD = 'gla_gbp_share_status';

/**
 * Post REST field holding the outcome of the post's last send to Google, and the message to show
 * the merchant for an outcome that needs their attention.
 */
export const SYNC_OUTCOME_FIELD = 'gla_gbp_sync_outcome';

/**
 * The sync outcome of a send that's scheduled but hasn't run yet.
 */
export const SYNC_OUTCOME_QUEUED = 'queued';
