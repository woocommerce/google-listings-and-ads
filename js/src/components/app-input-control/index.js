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

const baseClassName = 'app-input-control';

/**
 * Renders <InputControl> with a wrapper to be applicable extend additional class names.
 * noPointerEvents
 *
 * @param {Object} props React props to be forwarded to {@link InputControl}.
 * @param {string} [props.className] Additional CSS class name to be appended.
 * @param {boolean} [props.noPointerEvents] Whether disabled all pointer events. It will attach `pointer-events: none;` onto wrapper if true.
 */
const AppInputControl = forwardRef( ( props, ref ) => {
	const { className, noPointerEvents, ...rest } = props;
	const wrapperClassName = classnames(
		baseClassName,
		noPointerEvents && `${ baseClassName }--no-pointer-events`,
		className
	);

	return (
		<div className={ wrapperClassName }>
			<InputControl ref={ ref } { ...rest } />
		</div>
	);
} );

export default AppInputControl;
