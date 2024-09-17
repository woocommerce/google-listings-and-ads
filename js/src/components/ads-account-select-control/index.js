/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppSelectControl from '.~/components/app-select-control';
import useExistingGoogleAdsAccounts from '.~/hooks/useExistingGoogleAdsAccounts';

const AdsAccountSelectControl = ( props ) => {
	const { existingAccounts: accounts = [] } = useExistingGoogleAdsAccounts();

	const options = accounts.map( ( acc ) => ( {
		value: acc.id,
		label: sprintf(
			// translators: 1: account name, 2: account ID.
			__( '%1$s (%2$s)', 'google-listings-and-ads' ),
			acc.name,
			acc.id
		),
	} ) );

	return (
		<AppSelectControl
			selecSingleValue={ true }
			options={ options }
			{ ...props }
		/>
	);
};

export default AdsAccountSelectControl;
