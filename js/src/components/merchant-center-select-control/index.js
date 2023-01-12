/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useExistingGoogleMCAccounts from '.~/hooks/useExistingGoogleMCAccounts';
import AppSelectControl from '.~/components/app-select-control';

/**
 * @param {Object} props The component props
 * @param {string} [props.value] The selected value. IF no value is defined, then the first option is selected and onChange function is triggered.
 * @param {Function} [props.onChange] Callback when the select value changes.
 * @return {JSX.Element} An enhanced AppSelectControl component.
 */
const MerchantCenterSelectControl = ( {
	value,
	onChange = () => {},
	...props
} ) => {
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

	useEffect( () => {
		// Triggers the onChange event in order to pre-select the initial value
		if ( value === undefined ) {
			onChange( options[ 0 ].value );
		}
	}, [ options, onChange, value ] );

	return <AppSelectControl options={ options } { ...props } />;
};

export default MerchantCenterSelectControl;
