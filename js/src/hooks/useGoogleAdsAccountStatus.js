/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data/constants';
import useGoogleAdsAccount from './useGoogleAdsAccount';

const selectorName = 'getGoogleAdsAccountStatus';

const useGoogleAdsAccountStatus = () => {
	const { googleAdsAccount, isResolving } = useGoogleAdsAccount();

	return useSelect( ( select ) => {
		if ( ! googleAdsAccount || ! googleAdsAccount.id ) {
			return {
				hasAccess: undefined,
				inviteLink: undefined,
				isResolving,
			};
		}

		const selector = select( STORE_KEY );
		const { hasAccess, inviteLink } = selector[ selectorName ]();

		return {
			hasAccess,
			inviteLink,
			isResolving: selector.isResolving( selectorName, [] ),
		};
	} );
};

export default useGoogleAdsAccountStatus;
