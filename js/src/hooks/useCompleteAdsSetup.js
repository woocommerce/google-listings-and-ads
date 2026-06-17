/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';

export default function useCompleteAdsSetup() {
	const { createNotice } = useDispatchCoreNotices();

	const completeAdsSetup = useCallback( async () => {
		const options = {
			path: '/wc/gla/ads/setup/complete',
			method: 'POST',
		};

		try {
			return await apiFetch( options );
		} catch {
			createNotice(
				'error',
				__(
					'Unable to complete your ads setup. Please try again later.',
					'google-listings-and-ads'
				)
			);
			return await Promise.reject();
		}
	}, [ createNotice ] );

	return { completeAdsSetup };
}
