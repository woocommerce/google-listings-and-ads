/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import SelectControl from '.~/wcdl/select-control';
import useCountryKeyNameMap from '.~/hooks/useCountryKeyNameMap';

/**
 * Returns a SelectControl component with list of countries.
 *
 * The list of country options to be displayed can be set via the `options` props.
 *
 * Usage:
 *
 * ```
 * <AppCountrySelect
 * 		multiple
 * 		options={['AU', 'CN', 'US']}
 * 		value={['AU', 'US']}
 * 		onChange={(value) => {
 * 			console.log(value) // ['AU', 'US']
 * 		}}
 * />
 * // renders a SelectControl with three options, with 'AU' and 'US' selected.
 * ```
 *
 * @param {Object} props React props.
 */
const AppCountrySelect = ( props ) => {
	const { options = [], value = [], onChange = () => {}, ...rest } = props;
	const keyNameMap = useCountryKeyNameMap();
	const labelledOptions = options.map( ( option ) => {
		return {
			key: option,
			label: keyNameMap[ option ],
			value: { id: option },
		};
	} );

	const valueSet = new Set( value );
	const selected = labelledOptions.filter( ( el ) => valueSet.has( el.key ) );

	const handleChange = ( v ) => {
		const result = v.map( ( el ) => el.key );
		onChange( result );
	};

	return (
		<SelectControl
			isSearchable
			inlineTags
			options={ labelledOptions }
			placeholder={ __(
				'Start typing to filter countries…',
				'google-listings-and-ads'
			) }
			{ ...rest }
			selected={ selected }
			onChange={ handleChange }
		/>
	);
};

export default AppCountrySelect;
