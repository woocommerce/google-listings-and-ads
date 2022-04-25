/**
 * External dependencies
 */
import { Modal } from '@wordpress/components';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './index.scss';

const overflowYStyleName = {
	auto: 'app-modal__styled--overflow-y-auto',
	visible: 'app-modal__styled--overflow-y-visible',
};

/**
 * Renders a Modal component with additional commonly used features.
 *
 * @param {Object} props React props.
 * @param {string} [props.className] Additional CSS class name to be appended.
 * @param {'auto'|'visible'} [props.overflowY='auto'] Y-axis overflow style of modal.
 * @param {Array<JSX.Element>} [props.buttons] Buttons to be rendered at the modal footer.
 * @param {JSX.Element} props.children Content to be rendered.
 * @param {Object} props.rest Props to be forwarded to Modal.
 */
const AppModal = ( {
	className,
	overflowY = 'auto',
	buttons = [],
	children,
	...rest
} ) => {
	const modalClassName = classnames(
		'app-modal',
		overflowYStyleName[ overflowY ],
		className
	);

	return (
		<Modal className={ modalClassName } { ...rest }>
			{ children }
			{ buttons.length >= 1 && (
				<div className="app-modal__footer">{ buttons }</div>
			) }
		</Modal>
	);
};

export default AppModal;
