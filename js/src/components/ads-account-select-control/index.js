/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useExistingGoogleAdsAccounts from '.~/hooks/useExistingGoogleAdsAccounts';
import AppSelectControl from '.~/components/app-select-control';
import toAccountText from '.~/utils/toAccountText';

const AdsAccountSelectControl = ( props ) => {
	const { existingAccounts = [] } = useExistingGoogleAdsAccounts();

	const options = [
		{
			value: '',
			label: __( 'Select one', 'google-listings-and-ads' ),
		},
		...existingAccounts.map( ( acc ) => ( {
			value: acc,
			label: toAccountText( acc ),
		} ) ),
	];

	return <AppSelectControl options={ options } { ...props } />;
};

export default AdsAccountSelectControl;
