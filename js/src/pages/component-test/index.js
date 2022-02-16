/**
 * External dependencies
 */
import { useMemo, useState } from '@wordpress/element';
import { SelectControl } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import SupportedCountrySelect from '.~/components/supported-country-select';
import TreeSelectControl from '.~/components/tree-select-control';

const ComponentTest = () => {
	const [ value, setValue ] = useState();

	/**
	 * For LocalSelectControl
	 */
	const [ selected, setSelected ] = useState( [] );

	const treeSelectControlOptions = useMemo(
		() => [
			{
				id: 'EU',
				name: 'Europe',
				children: [
					{ id: 'ES', name: 'Spain' },
					{ id: 'FR', name: 'France' },
					{ id: 'IT', name: 'Italy' },
				],
			},
			{
				id: 'AS',
				name: 'Asia',
				children: [
					{ id: 'JP', name: 'Japan' },
					{ id: 'CH', name: 'China' },
					{ id: 'MY', name: 'Malaysia' },
				],
			},
		],
		[]
	);

	return (
		<div>
			<h2>TreeSelectControl</h2>
			<TreeSelectControl
				options={ treeSelectControlOptions }
				value={ selected }
				onChange={ setSelected }
				label="Select Country"
				placeholder="Select"
			/>
			<hr />
			<hr />
			<h2>Existing SupportedCountrySelect:</h2>
			<SupportedCountrySelect
				multiple
				value={ value }
				onChange={ setValue }
			/>
			<hr></hr>
			<h2>@woocommerce/components SelectControl with multiple:</h2>
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
