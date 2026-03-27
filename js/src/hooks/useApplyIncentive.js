/**
 * External dependencies
 */
import { useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import { API_NAMESPACE } from '~/data/constants';

/**
 * Custom hook that returns a function to apply an ads credit incentive.
 *
 * The returned function posts the selected incentive ID to the API. If no incentive
 * is selected the call is skipped and the function resolves to `true` so the caller
 * can proceed normally. On API failure, a notice is shown to the merchant and the
 * function resolves to `false` so the caller can abort the current action.
 *
 * @return {Function} applyIncentive - Async function that accepts an `incentiveId`
 *   and returns `true` when the request succeeds (or is skipped) and `false` on error.
 */
const useApplyIncentive = () => {
	const { createNotice } = useDispatchCoreNotices();

	const applyIncentive = useCallback(
		async ( incentiveId ) => {
			if ( ! incentiveId ) {
				return true;
			}

			try {
				await apiFetch( {
					path: `${ API_NAMESPACE }/ads/incentive`,
					method: 'POST',
					data: { id: incentiveId },
				} );
				return true;
			} catch ( e ) {
				createNotice(
					'error',
					__(
						'Unable to apply the selected ads credit offer.',
						'google-listings-and-ads'
					)
				);
				return false;
			}
		},
		[ createNotice ]
	);

	return applyIncentive;
};

export default useApplyIncentive;
