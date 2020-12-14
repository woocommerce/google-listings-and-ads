/**
 * External dependencies
 */
import { Modal } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './index.scss';

const AppModal = ( props ) => {
	const { buttons = [], className, children, ...rest } = props;

	return (
		<Modal className={ `gla-app-modal ${ className }` } { ...rest }>
			{ children }
			{ buttons.length >= 1 && (
				<div className="gla-app-modal__footer">{ buttons }</div>
			) }
		</Modal>
	);
};

export default AppModal;
