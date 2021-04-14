/**
 * External dependencies
 */
import { useState } from '@wordpress/element';

/**
 * A convenience component to glue button and modal together.
 *
 * Upon button click, this component will open the modal automatically.
 *
 * Upon modal's onRequestClose, this component will close the modal automatically.
 *
 * ## Usage
 *
 * ```js
 * <AppModalButton
		button={
			<AppTextButton>
				Click to open MySuperModal
			</AppTextButton>
		}
		modal={ <MySuperModal /> }
	/>
 * ```
 *
 * @param {Object} props Props.
 * @param {Object} props.button Button component.
 * @param {Object} props.modal Modal component.
 */
const AppModalButton = ( props ) => {
	const { button, modal } = props;
	const { onClick = () => {} } = button.props;
	const { onRequestClose = () => {} } = modal.props;
	const [ isOpen, setOpen ] = useState( false );

	const handleButtonClick = ( ...args ) => {
		setOpen( true );
		onClick( ...args );
	};

	const handleModalRequestClose = ( ...args ) => {
		setOpen( false );
		onRequestClose( ...args );
	};

	button.props = {
		...button.props,
		onClick: handleButtonClick,
	};

	modal.props = {
		...modal.props,
		onRequestClose: handleModalRequestClose,
	};

	return (
		<>
			{ button }
			{ isOpen && modal }
		</>
	);
};

export default AppModalButton;
