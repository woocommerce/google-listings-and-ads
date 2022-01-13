/**
 * External dependencies
 */
import { SelectControl } from '@wordpress/components';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import SupportedCountrySelect from '.~/components/supported-country-select';

const ComponentTest = () => {
	const [ value, setValue ] = useState();

	return (
		<div>
			<h2>Existing SupportedCountrySelect:</h2>
			<SupportedCountrySelect
				multiple
				value={ value }
				onChange={ setValue }
			/>
			<hr></hr>
			<h2>@wordpress/components SelectControl with multiple:</h2>
			<SelectControl
				multiple
				options={ [
					{ value: null, label: 'Select a User', disabled: true },
					{ value: 'a', label: 'User A' },
					{ value: 'b', label: 'User B' },
					{ value: 'c', label: 'User c' },
				] }
			/>
		</div>
	);
};

export default ComponentTest;
