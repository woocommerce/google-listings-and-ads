/**
 * External dependencies
 */
import { Modal } from '@wordpress/components';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './index.scss';

/**
 * Renders a Modal component with additional commonly used features.
 *
 * @param {Object} props React props.
 * @param {string} [props.className] Additional CSS class name to be appended.
 * @param {Array<JSX.Element>} [props.buttons] Buttons to be rendered at the modal footer.
 * @param {JSX.Element} props.children Content to be rendered.
 * @param {Object} props.rest Props to be forwarded to Modal.
 */
const AppModal = ( { className, buttons = [], children, ...rest } ) => {
	return (
		<Modal className={ classnames( 'app-modal', className ) } { ...rest }>
			{ children }
			{ buttons.length >= 1 && (
				<div className="app-modal__footer">{ buttons }</div>
			) }
		</Modal>
	);
};

export default AppModal;
