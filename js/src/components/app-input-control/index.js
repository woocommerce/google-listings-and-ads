/**
 * External dependencies
 */
import { __experimentalInputControl as InputControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './index.scss';

const AppInputControl = ( props ) => {
	const { className = '', ...rest } = props;

	return (
		<div className={ `app-input-control ${ className }` }>
			<InputControl { ...rest } />
		</div>
	);
};

export default AppInputControl;
