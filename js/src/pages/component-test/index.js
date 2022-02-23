/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { SelectControl } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import SupportedCountrySelect from '.~/components/supported-country-select';
import TreeSelectControl from '.~/components/tree-select-control';
import './index.scss';

const ComponentTest = () => {
	const [ value, setValue ] = useState();

	/**
	 * For LocalSelectControl
	 */
	const [ selected, setSelected ] = useState( [ 'ES' ] );
	const [ disabled, setDisabled ] = useState( false );

	const treeSelectControlOptions = [
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
	];

	return (
		<div>
			<button
				onClick={ () => {
					setDisabled( ! disabled );
				} }
			>
				Toggle Disable
			</button>
			<h2>TreeSelectControl</h2>
			<TreeSelectControl
				disabled={ disabled }
				options={ treeSelectControlOptions }
				value={ selected }
				onChange={ setSelected }
				label="Select Country"
				placeholder="Select"
			/>
			<hr />
			<hr />
			<h2>TreeSelectControl Custom style</h2>
			<TreeSelectControl
				className="gla-tree-select-control"
				disabled={ disabled }
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
				disabled={ disabled }
				value={ value }
				onChange={ setValue }
			/>
			<hr></hr>
			<h2>@woocommerce/components SelectControl with multiple:</h2>
			<SelectControl
				multiple
				disabled={ disabled }
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
