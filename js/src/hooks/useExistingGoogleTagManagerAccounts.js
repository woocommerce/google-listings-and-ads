/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '~/data/constants';

const useExistingGoogleTagManagerAccounts = () => {
	return useSelect( ( select ) => {
		const existingAccounts =
			select( STORE_KEY ).getExistingGoogleTagManagerAccounts();
		const hasFinishedResolution = select( STORE_KEY ).hasFinishedResolution(
			'getExistingGoogleTagManagerAccounts'
		);

		return { existingAccounts, hasFinishedResolution };
	}, [] );
};

export default useExistingGoogleTagManagerAccounts;
