/**
 * External dependencies
 */
import classnames from 'classnames';
import { forwardRef } from '@wordpress/element';
import { __experimentalInputControl as InputControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './index.scss';

const AppInputControl = forwardRef( ( props, ref ) => {
	const { className, ...rest } = props;

	return (
		<div className={ classnames( 'app-input-control', className ) }>
			<InputControl ref={ ref } { ...rest } />
		</div>
	);
} );

export default AppInputControl;
