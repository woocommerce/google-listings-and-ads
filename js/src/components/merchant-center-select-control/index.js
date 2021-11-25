/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useExistingGoogleMCAccounts from '.~/hooks/useExistingGoogleMCAccounts';
import AppSelectControl from '.~/components/app-select-control';

const MerchantCenterSelectControl = ( props ) => {
	const { data: existingAccounts = [] } = useExistingGoogleMCAccounts();

	const options = existingAccounts.map( ( acc ) => {
		return {
			value: acc.id,
			label: `${ acc.name } ・ ${ acc.domain } (${ acc.id })`,
		};
	} );
	options.sort( ( a, b ) => {
		return a.label.localeCompare( b.label );
	} );
	options.unshift( {
		value: '',
		label: __( 'Select one', 'google-listings-and-ads' ),
	} );

	return <AppSelectControl options={ options } { ...props } />;
};

export default MerchantCenterSelectControl;
