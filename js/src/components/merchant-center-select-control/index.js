/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

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
			label: sprintf(
				// translators: 1: account name, 2: account domain, 3: account ID.
				__( '%1$s ・ %2$s (%3$s)', 'google-listings-and-ads' ),
				acc.name,
				acc.domain,
				acc.id
			),
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
