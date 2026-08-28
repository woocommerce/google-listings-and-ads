/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { getSetting } from '@woocommerce/settings'; // eslint-disable-line import/no-unresolved

/**
 * Internal dependencies
 */
import MerchantCenterSelectControl from '~/components/merchant-center-select-control';
import useExistingGoogleMCAccounts from '~/hooks/useExistingGoogleMCAccounts';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import AppSelectControl from '~/components/app-select-control';

/**
 * Renders the connected Merchant Center details, leveraging existing functionality
 * and styles from AppSelectControl.
 *
 * Ideally, using MerchantCenterSelectControl only to display the list of MC accounts would be fine. However, during testing,
 * we found that when a URL is reclaimed for Merchant Center (MC), the Google API does not
 * return the newly reclaimed account immediately in the list provided by the useExistingGoogleMCAccounts hook,
 * even though the data in the store is invalidated. In that case, thus we end up having an account ID
 * which is not in the list of existing accounts. We then fake the connected select by manually providing
 * the connected ID.
 *
 * @param {Object} props
 * @param {boolean} props.isConnected Whether the Merchant Center account is connected.
 */
const MerchantCenterSelect = ( { isConnected, ...rest } ) => {
	const { data: existingAccounts } = useExistingGoogleMCAccounts();
	const { googleMCAccount } = useGoogleMCAccount();

	const accountIdExists = existingAccounts?.some(
		( existingAccount ) => existingAccount.id === googleMCAccount.id
	);

	// If the account ID is not in the list of existing accounts, fake the select options by displaying the connected account ID only.
	if ( ! accountIdExists && isConnected ) {
		const domain = new URL( getSetting( 'homeUrl' ) ).host;

		return (
			<AppSelectControl
				options={ [
					{
						value: googleMCAccount.id,
						label: sprintf(
							// translators: 1: account domain, 2: account ID.
							__( '%1$s (%2$s)', 'google-listings-and-ads' ),
							domain,
							googleMCAccount.id
						),
					},
				] }
				value={ googleMCAccount.id }
				autoSelectFirstOption
				nonInteractive
			/>
		);
	}

	return (
		<MerchantCenterSelectControl
			nonInteractive={ isConnected }
			{ ...rest }
		/>
	);
};

export default MerchantCenterSelect;
