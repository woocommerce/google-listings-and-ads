/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import { getHistory, getNewPath, getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { INCENTIVE_APPLIED_QUERY } from '~/constants';
import useDispatchCoreNotices from './useDispatchCoreNotices';

/**
 * Shows a notice that the selected Ads credit offer was applied, when the URL has the
 * `INCENTIVE_APPLIED_QUERY` arg. Then removes the arg, so a refresh doesn't show it again.
 *
 * Waits until any guide modal on the page is closed, so the notice isn't hidden behind it.
 */
export default function useIncentiveAppliedNoticeEffect() {
	const { createNotice } = useDispatchCoreNotices();
	const query = getQuery();
	const shouldShow =
		query.incentive === INCENTIVE_APPLIED_QUERY.incentive && ! query.guide;

	useEffect( () => {
		if ( ! shouldShow ) {
			return;
		}

		createNotice(
			'success',
			__(
				'Your Ads credit offer is applied to your Google Ads account.',
				'google-listings-and-ads'
			)
		);

		getHistory().replace(
			getNewPath( { ...getQuery(), incentive: undefined } )
		);
	}, [ shouldShow, createNotice ] );
}
