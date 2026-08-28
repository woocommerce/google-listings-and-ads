/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useTargetAudienceFinalCountryCodes from '~/hooks/useTargetAudienceFinalCountryCodes';
import useSettings from '~/hooks/useSettings';
import AppButton from '~/components/app-button';
import AddMarketModal from './add-market-modal';

/**
 * Event fired when the "Add market" button is clicked.
 *
 * @event gla_add_market_button_clicked
 */

/**
 * Component for the "Add market" button on the markets page, which opens a modal to add a new market.
 *
 * @fires gla_add_market_button_clicked event when the button is clicked
 */
const AddMarketButton = () => {
	const [ isOpen, setIsOpen ] = useState( false );
	const { targetAudience, loaded: hasResolvedTargetAudience } =
		useTargetAudienceFinalCountryCodes();
	const { settings } = useSettings();

	const handleOpen = useCallback( () => setIsOpen( true ), [] );
	const handleClose = useCallback( () => setIsOpen( false ), [] );

	return (
		<>
			<AppButton
				eventName="gla_add_market_button_clicked"
				loading={
					isOpen && ( ! hasResolvedTargetAudience || ! settings )
				}
				onClick={ handleOpen }
				variant="primary"
			>
				{ __( 'Add market', 'google-listings-and-ads' ) }
			</AppButton>

			{ isOpen && hasResolvedTargetAudience && (
				<AddMarketModal
					onRequestClose={ handleClose }
					settings={ settings }
					targetAudience={ targetAudience }
				/>
			) }
		</>
	);
};

export default AddMarketButton;
