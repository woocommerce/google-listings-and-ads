/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';
import { getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { getDashboardUrl } from '.~/utils/urls';
import useAppSelectDispatch from '.~/hooks/useAppSelectDispatch';
import AppSpinner from '.~/components/app-spinner';
import DisconnectAccountCard from './disconnect-account-card';

export default function ReconnectAccounts() {
	const { data } = useAppSelectDispatch( 'getGoogleAccountAccess' );

	const googleHasFullAccess =
		data?.active === 'yes' &&
		data?.merchant_access !== 'no' &&
		data?.ads_access !== 'no';

	useEffect( () => {
		if ( googleHasFullAccess ) {
			getHistory().replace( getDashboardUrl() );
		}
	}, [ googleHasFullAccess ] );

	if ( ! data ) {
		return <AppSpinner />;
	}

	if ( ! googleHasFullAccess ) {
		return <DisconnectAccountCard { ...data } />;
	}

	// Renders nothing because it's waiting for the redirection process above.
	return null;
}
