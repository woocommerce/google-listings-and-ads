/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import { getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { getDashboardUrl } from '.~/utils/urls';
import toScopeState from '.~/utils/toScopeState';
import useAppSelectDispatch from '.~/hooks/useAppSelectDispatch';
import Section from '.~/wcdl/section';
import AppSpinner from '.~/components/app-spinner';
import GoogleAccountCard from '.~/components/google-account-card';
import DisconnectAccountCard from './disconnect-account-card';

export default function ReconnectAccounts() {
	const { data } = useAppSelectDispatch( 'getGoogleAccountAccess' );
	const scope = toScopeState( data?.scope );

	const isConnected = data?.active === 'yes';

	// It's undefined before connected.
	const noAccess = isConnected
		? data?.merchant_access === 'no' || data?.ads_access === 'no'
		: undefined;

	const isCompletedReconnection =
		isConnected && ! noAccess && scope.glaRequired;

	useEffect( () => {
		if ( isCompletedReconnection ) {
			getHistory().replace( getDashboardUrl() );
		}
	}, [ isCompletedReconnection ] );

	if ( ! data ) {
		return <AppSpinner />;
	}

	if ( ! isCompletedReconnection ) {
		/**
		 * Ask user to disconnect is a priority action if user reconnect to an account
		 * that has no access to its previously connected Google Merchant Center account
		 * or Google Ads account.
		 *
		 * Otherwise, ask for (re)authorizing their Google account
		 * if not yet connected or no required permission scopes.
		 */
		const card = noAccess ? (
			<DisconnectAccountCard email={ data.email } />
		) : (
			<GoogleAccountCard />
		);

		return (
			<Section
				title={ __( 'Connect account', 'google-listings-and-ads' ) }
			>
				{ card }
			</Section>
		);
	}

	// Renders nothing because it's waiting for the redirection process above.
	return null;
}
