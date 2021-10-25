/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data/constants';

const selectorName = 'getExistingGoogleMCAccounts';

const useExistingGoogleMCAccounts = () => {
	return useSelect( ( select ) => {
		const selector = select( STORE_KEY );

		return {
			existingAccounts: selector[ selectorName ](),
			isResolving: selector.isResolving( selectorName ),
			hasFinishedResolution: selector.hasFinishedResolution(
				selectorName
			),
		};
	}, [] );
};

export default useExistingGoogleMCAccounts;
