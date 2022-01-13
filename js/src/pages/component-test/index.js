/**
 * External dependencies
 */
import { SelectControl } from '@wordpress/components';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import SupportedCountrySelect from '.~/components/supported-country-select';
import TreeSelectControl from '.~/components/tree-select-control';
import LocalSelectControl from '.~/components/select-control';
import useCountryKeyNameMap from '.~/hooks/useCountryKeyNameMap';

const ComponentTest = () => {
	const [ value, setValue ] = useState();

	/**
	 * For LocalSelectControl
	 */
	const [ selected, setSelected ] = useState( [] );
	const keyNameMap = useCountryKeyNameMap();
	const options = [ 'AU', 'CN', 'US' ];
	const labelledOptions = options.map( ( option ) => {
		return {
			key: option,
			label: keyNameMap[ option ],
			value: { id: option },
		};
	} );

	return (
		<div>
			<h2>TreeSelectControl</h2>
			<TreeSelectControl />
			<hr />
			<h2>Local SelectControl</h2>
			<LocalSelectControl
				multiple
				inlineTags
				isSearchable
				options={ labelledOptions }
				selected={ selected }
				onChange={ setSelected }
			/>
			<hr />
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
