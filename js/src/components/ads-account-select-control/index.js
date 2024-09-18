/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppSelectControl from '.~/components/app-select-control';
import useExistingGoogleAdsAccounts from '.~/hooks/useExistingGoogleAdsAccounts';

/**
 * @param {Object} props The component props
 * @param {string} [props.value] The selected value. IF no value is defined, then the first option is selected and onChange function is triggered.
 * @param {Function} [props.onChange] Callback when the select value changes.
 * @return {JSX.Element} An enhanced AppSelectControl component.
 */
const AdsAccountSelectControl = ( {
	value,
	onChange = () => {},
	...props
} ) => {
	const { existingAccounts: accounts = [] } = useExistingGoogleAdsAccounts();

	const options = accounts.map( ( acc ) => ( {
		value: acc.id,
		label: `${ acc.name } (${ acc.id })`,
	} ) );

	useEffect( () => {
		// Triggers the onChange event in order to pre-select the initial value
		if ( value === undefined ) {
			onChange( options[ 0 ]?.value );
		}
	}, [ options, onChange, value ] );

	return (
		<AppSelectControl
			options={ options }
			onChange={ onChange }
			selectSingleValue
			{ ...props }
		/>
	);
};

export default AdsAccountSelectControl;
