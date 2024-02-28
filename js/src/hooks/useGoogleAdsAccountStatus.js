/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data/constants';

const selectorName = 'getAdsAccountStatus';

const useGoogleAdsAccountStatus = () => {
	return useSelect( ( select ) => {
		const selector = select( STORE_KEY );
		const { hasAccess, inviteLink } = selector[ selectorName ]();

		return {
			hasAccess,
			inviteLink,
			hasFinishedResolution: selector.hasFinishedResolution(
				selectorName,
				[]
			),
		};
	} );
};

export default useGoogleAdsAccountStatus;
