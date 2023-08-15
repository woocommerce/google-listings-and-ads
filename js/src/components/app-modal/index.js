/**
 * External dependencies
 */
import { Modal } from '@wordpress/components';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './index.scss';

const overflowStyleName = {
	/**
	 * In @wordpress/components 14.2.0, the overflow of Modal container will be
	 * changed from `auto` to `hidden`, and the change also adds `auto` to Modal body.
	 * The `auto` is set as the default overflow in this component's CSS to make it
	 * have a stable overflow style across these versions.
	 *
	 * References:
	 * - https://github.com/WordPress/gutenberg/blob/%40wordpress/components%4012.0.9/packages/components/src/modal/style.scss#L29
	 * - https://github.com/WordPress/gutenberg/blob/%40wordpress/components%4014.2.0/packages/components/src/modal/style.scss#L24
	 */
	auto: false,
	visible: 'app-modal__styled--overflow-visible',
};

/**
 * Renders a Modal component with additional commonly used features.
 *
 * @param {Object} props React props.
 * @param {string} [props.className] Additional CSS class name to be appended.
 * @param {'auto'|'visible'} [props.overflow='auto'] Overflow style of modal.
 * @param {Array<JSX.Element>} [props.buttons] Buttons to be rendered at the modal footer.
 * @param {JSX.Element} props.children Content to be rendered.
 * @param {Object} props.rest Props to be forwarded to Modal.
 */
const AppModal = ( {
	className,
	overflow = 'auto',
	buttons = [],
	children,
	...rest
} ) => {
	const modalClassName = classnames(
		// gla-admin-page is for scoping particular styles to components that are used by
		// a GLA admin page and are rendered via ReactDOM.createPortal.
		'gla-admin-page',
		'app-modal',
		overflowStyleName[ overflow ],
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
