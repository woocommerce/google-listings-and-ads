/**
 * External dependencies
 */
import { SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useExistingGoogleMCAccounts from '.~/hooks/useExistingGoogleMCAccounts';

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

	return (
		<div className="gla-account-select-control">
			<SelectControl options={ options } { ...props } />
		</div>
	);
};

export default MerchantCenterSelectControl;
