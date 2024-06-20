/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data/constants';

const selectorName = 'getGoogleAdsAccountStatus';

const useGoogleAdsAccountStatus = () => {
	return useSelect( ( select ) => {
		const selector = select( STORE_KEY );
		const { hasAccess, inviteLink, step } = selector[ selectorName ]();

		return {
			hasAccess,
			inviteLink,
			step,
			hasFinishedResolution:
				selector.hasFinishedResolution( selectorName ),
		};
	}, [] );
};

export default useGoogleAdsAccountStatus;
