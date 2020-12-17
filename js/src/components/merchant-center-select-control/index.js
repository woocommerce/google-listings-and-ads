/**
 * External dependencies
 */
import { SelectControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './index.scss';

const MerchantCenterSelectControl = ( props ) => {
	const { value, onChange } = props;

	// TODO: list of merchant center accounts that can be connected.
	// This should come from backend API.
	const options = [
		{
			value: 123,
			label: 'Test MC account',
		},
	];

	return (
		<div className="gla-merchant-center-select-control">
			<SelectControl
				options={ options }
				value={ value }
				onChange={ onChange }
			/>
		</div>
	);
};

export default MerchantCenterSelectControl;
