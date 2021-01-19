/**
 * External dependencies
 */
import { SelectControl } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './index.scss';

const AppCountryMultiSelect = ( props ) => {
	const { value = [], className = '', onChange = () => {}, ...rest } = props;

	// TODO: get list of countries from backend API.
	const options = [
		{
			key: 'AU',
			label: 'Australia',
			value: { id: 'AUS' },
		},
		{
			key: 'US',
			label: 'United States of America',
			value: { id: 'USA' },
		},
	];

	const valueSet = new Set( value );
	const selected = options.filter( ( el ) => valueSet.has( el.key ) );

	const handleChange = ( v ) => {
		const result = v.map( ( el ) => el.key );
		onChange( result );
	};

	return (
		<SelectControl
			className={ `app-country-multi-select ${ className }` }
			multiple
			isSearchable
			inlineTags
			options={ options }
			placeholder={ __(
				'Start typing to filter countries…',
				'google-listings-and-ads'
			) }
			selected={ selected }
			onChange={ handleChange }
			{ ...rest }
		/>
	);
};

export default AppCountryMultiSelect;
