/**
 * External dependencies
 */
import { useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import { ERROR_SLOTS } from '~/data/constants';

/**
 * Returns a callback to clear the Google Ads connection error slot.
 *
 * @return {Function} Callback that clears the Google Ads connection error slot.
 */
const useClearAdsConnectionError = () => {
	const { clearDetailedErrorBySlots } = useAppDispatch();

	return useCallback( () => {
		clearDetailedErrorBySlots( [
			ERROR_SLOTS.GOOGLE_ADS_CONNECTION_ERROR_SLOT,
		] );
	}, [ clearDetailedErrorBySlots ] );
};

export default useClearAdsConnectionError;
