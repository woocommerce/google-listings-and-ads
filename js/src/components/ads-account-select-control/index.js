/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { getSetting } from '@woocommerce/settings'; // eslint-disable-line import/no-unresolved

/**
 * Internal dependencies
 */
import AppSelectControl from '~/components/app-select-control';
import useExistingGoogleAdsAccounts from '~/hooks/useExistingGoogleAdsAccounts';
import useGoogleAdsAccount from '~/hooks/useGoogleAdsAccount';

/**
 * @param {Object} props The component props
 * @return {JSX.Element} An enhanced AppSelectControl component.
 */
const AdsAccountSelectControl = ( props ) => {
	const { existingAccounts } = useExistingGoogleAdsAccounts();
	const { googleAdsAccount, hasGoogleAdsConnection } = useGoogleAdsAccount();

	const accountIdExists = existingAccounts?.some(
		( existingAccount ) => existingAccount.id === googleAdsAccount?.id
	);

	// If the account ID is not in the list of existing accounts, fake the select options by displaying the connected account ID only.
	if ( ! accountIdExists && hasGoogleAdsConnection ) {
		const domain = new URL( getSetting( 'homeUrl' ) ).host;

		return (
			<AppSelectControl
				options={ [
					{
						value: googleAdsAccount.id,
						label: sprintf(
							// translators: 1: account domain, 2: account ID.
							__( '%1$s (%2$s)', 'google-listings-and-ads' ),
							domain,
							googleAdsAccount.id
						),
					},
				] }
				value={ googleAdsAccount.id }
				autoSelectFirstOption
				nonInteractive
			/>
		);
	}

	const options = existingAccounts?.map( ( acc ) => ( {
		value: acc.id,
		label: `${ acc.name } (${ acc.id })`,
	} ) );

	return (
		<AppSelectControl
			options={ options }
			autoSelectFirstOption
			{ ...props }
		/>
	);
};

export default AdsAccountSelectControl;
