/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '~/components/account-card';
import ConnectedIconLabel from '~/components/connected-icon-label';
import LoadingLabel from '~/components/loading-label';
import EnableNewProductSyncButton from '~/components/enable-new-product-sync-button';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';

/**
 * Renders a card for asking the user to grant access to the WordPress.com app,
 * which authorizes Google's shopping data integration API to fetch WooCommerce
 * products etc through the app.
 *
 * Please note that the authorization process involves multiple external
 * services, this component may be technically ambiguous in these places:
 * - Component name
 * - The directory where it is located
 * - The data source for its grant status
 * - The presenation on UI
 */
export default function WPComAppAuthorizationCard() {
	const { hasFinishedResolution, isWPComAppGranted } = useGoogleMCAccount();

	const getIndicator = () => {
		if ( ! hasFinishedResolution ) {
			return <LoadingLabel />;
		}

		if ( isWPComAppGranted ) {
			return (
				<ConnectedIconLabel
					text={ __( 'Access granted', 'google-listings-and-ads' ) }
				/>
			);
		}

		return (
			<EnableNewProductSyncButton
				text={ __( 'Grant access', 'google-listings-and-ads' ) }
				eventProps={ { page: 'setup-mc' } }
			/>
		);
	};

	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_MERCHANT_CENTER }
			title={ __(
				`Google's WordPress.com application`,
				'google-listings-and-ads'
			) }
			description={ __(
				'Granting access to the application is required in order to synchronize product data with Google through it.',
				'google-listings-and-ads'
			) }
			indicator={ getIndicator() }
		/>
	);
}
