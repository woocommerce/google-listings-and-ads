/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import { getHistory, getNewPath, getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import useDispatchCoreNotices from './useDispatchCoreNotices';

/**
 * The one-time query arg added to a redirect URL after an Ads credit offer was applied.
 * Flows that end with a full page reload use it, since a notice created before the reload is lost.
 */
export const INCENTIVE_APPLIED_QUERY = { incentive: 'applied' };

/**
 * Shows a notice that the selected Ads credit offer was applied, when the URL has the
 * `INCENTIVE_APPLIED_QUERY` arg. Then removes the arg, so a refresh doesn't show it again.
 *
 * Waits until any guide modal on the page is closed, so the notice isn't hidden behind it.
 */
export default function useIncentiveAppliedNotice() {
	const { createNotice } = useDispatchCoreNotices();
	const query = getQuery();
	const shouldShow =
		query?.incentive === INCENTIVE_APPLIED_QUERY.incentive && ! query.guide;

	useEffect( () => {
		if ( ! shouldShow ) {
			return;
		}

		createNotice(
			'success',
			__(
				'The selected Ads credit offer was successfully applied to your account.',
				'google-listings-and-ads'
			)
		);

		getHistory().replace(
			getNewPath( { ...getQuery(), incentive: undefined } )
		);
	}, [ shouldShow, createNotice ] );
}
