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
	const { value = [], onChange = () => {} } = props;

	// TODO: get list of countries from backend API.
	const options = [
		{
			key: 'AUS',
			label: 'Australia',
			value: { id: 'AUS' },
		},
		{
			key: 'USA',
			label: 'United States of America',
			value: { id: 'USA' },
		},
	];

	return (
		<SelectControl
			className="app-country-multi-select"
			multiple
			isSearchable
			inlineTags
			options={ options }
			placeholder={ __(
				'Start typing to filter countries…',
				'google-listings-and-ads'
			) }
			selected={ value }
			onChange={ onChange }
		/>
	);
};

export default AppCountryMultiSelect;
