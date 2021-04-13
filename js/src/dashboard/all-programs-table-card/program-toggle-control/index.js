/**
 * External dependencies
 */
import { ToggleControl } from '@wordpress/components';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './index.scss';

const ProgramToggleControl = ( props ) => {
	const { program } = props;
	const [ checked, setChecked ] = useState( program.active );

	// TODO: check value and show popup modal.
	const handleChange = ( v ) => {
		setChecked( v );
	};

	return (
		<ToggleControl
			className="gla-program-toggle-control"
			checked={ checked }
			onChange={ handleChange }
		/>
	);
};

export default ProgramToggleControl;
