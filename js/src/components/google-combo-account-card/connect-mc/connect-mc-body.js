/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import MerchantCenterSelectControl from '.~/components/merchant-center-select-control';
import AppButton from '.~/components/app-button';
import ContentButtonLayout from '.~/components/content-button-layout';
import ConnectedIconLabel from '.~/components/connected-icon-label';

/**
 * Clicking on the button to connect an existing Google Merchant Center account.
 *
 * @event gla_mc_account_connect_button_click
 * @property {number} id The account ID to be connected.
 */

/**
 * ConnectMCBody component.
 *
 * This component renders the body section for the Merchant Center connection.
 * It displays a selection control for choosing an account, and conditionally renders
 * a connected status icon or a button to connect to the selected Merchant Center account.
 *
 * @param {Object}   props
 * @param {string}   props.value The selected Merchant Center account ID.
 * @param {Function} props.setValue Callback to update the selected account value.
 * @param {boolean}  props.isConnected Whether the Merchant Center account is connected.
 * @param {Function} props.handleConnectMC Callback function to handle the connection process.
 * @param {Object}   props.resultConnectMC The result of the connection request, used to handle loading state.
 *
 * @fires gla_mc_account_connect_button_click
 */
const ConnectMCBody = ( {
	value,
	setValue,
	isConnected,
	handleConnectMC,
	resultConnectMC,
} ) => {
	return (
		<ContentButtonLayout>
			<MerchantCenterSelectControl
				value={ value }
				onChange={ setValue }
			/>

			{ isConnected && (
				<ConnectedIconLabel className="gla-google-combo-service-connected-icon-label" />
			) }

			{ ! isConnected && (
				<AppButton
					isSecondary
					loading={ resultConnectMC.loading }
					disabled={ ! value }
					eventName="gla_mc_account_connect_button_click"
					eventProps={ { id: Number( value ) } }
					onClick={ handleConnectMC }
				>
					{ __( 'Connect', 'google-listings-and-ads' ) }
				</AppButton>
			) }
		</ContentButtonLayout>
	);
};

export default ConnectMCBody;
