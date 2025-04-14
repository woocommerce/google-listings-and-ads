/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex, FlexBlock, Notice } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AccountCard, { APPEARANCE } from '~/components/account-card';
import LoadingLabel from '~/components/loading-label';
import EnableNewProductSyncButton from '~/components/enable-new-product-sync-button';
import { SwitchAccountButton } from '~/components/google-account-card';
import useGoogleAccount from '~/hooks/useGoogleAccount';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import { GOOGLE_WPCOM_APP_CONNECTED_STATUS } from '~/constants';

function getDetail( status ) {
	if ( status === GOOGLE_WPCOM_APP_CONNECTED_STATUS.DISAPPROVED ) {
		return (
			<Notice status="warning" isDismissible={ false }>
				{ __(
					'Access was denied. Please make sure to grant Google access to your WooCommerce store to continue.',
					'google-listings-and-ads'
				) }
			</Notice>
		);
	}
	if ( status === GOOGLE_WPCOM_APP_CONNECTED_STATUS.ERROR ) {
		return (
			<Notice status="error" isDismissible={ false }>
				{ __(
					'There was an error granting Google access to your WooCommerce store. Please try again, or contact support for further help.',
					'google-listings-and-ads'
				) }
			</Notice>
		);
	}

	return null;
}

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
 *
 * @param {Object} props React props.
 * @param {Object} props.eventPropsOfEnableButton Event tracking properties for the enable button.
 * @param {boolean} [props.hideAccountSwitch=false] Whether to hide the account switch button.
 */
export default function AuthorizeWPComAppCard( {
	eventPropsOfEnableButton,
	hideAccountSwitch = false,
} ) {
	const { google } = useGoogleAccount();
	const { googleMCAccount, hasFinishedResolution } = useGoogleMCAccount();

	const getIndicator = () => {
		if ( ! hasFinishedResolution ) {
			return <LoadingLabel />;
		}

		return (
			<EnableNewProductSyncButton
				eventProps={ eventPropsOfEnableButton }
			/>
		);
	};

	const detail = getDetail( googleMCAccount.wpcom_rest_api_status );
	const alignment = detail ? 'top' : undefined;

	return (
		<AccountCard
			className="gla-authorize-wpcom-app-card"
			appearance={ APPEARANCE.GOOGLE }
			description={
				<Flex direction="column" gap={ 3 }>
					<FlexBlock>{ google?.email }</FlexBlock>
					<FlexBlock>
						{ __(
							'Granting Google access to your WooCommerce store is required in order to synchronize product data with Google.',
							'google-listings-and-ads'
						) }
					</FlexBlock>
				</Flex>
			}
			alignIcon={ alignment }
			alignIndicator={ alignment }
			expandedDetail={ Boolean( detail ) }
			indicator={ getIndicator() }
			detail={ detail }
			actions={ ! hideAccountSwitch && <SwitchAccountButton /> }
		/>
	);
}
