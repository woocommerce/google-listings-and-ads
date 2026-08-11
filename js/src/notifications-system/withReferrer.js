/**
 * External dependencies
 */
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { REFERRER_TYPE_NOTIFICATION } from '~/utils/tracks';

/**
 * Appends the notification's referrer info to a CTA href, so the destination
 * flow can attribute its own tracking events back to this notification.
 *
 * @param {string} href Original CTA destination.
 * @param {string} notificationId Notification ID to attribute the referral to.
 * @return {string} `href` with `referrer_type`/`referrer_id` query params appended.
 */
const withReferrer = ( href, notificationId ) => {
	return addQueryArgs( href, {
		referrer_type: REFERRER_TYPE_NOTIFICATION,
		referrer_id: notificationId,
	} );
};

export default withReferrer;
