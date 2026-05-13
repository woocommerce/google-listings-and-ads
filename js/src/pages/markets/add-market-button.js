/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useShippingRates from '~/hooks/useShippingRates';
import useShippingTimes from '~/hooks/useShippingTimes';
import useTargetAudienceFinalCountryCodes from '~/hooks/useTargetAudienceFinalCountryCodes';
import AppButton from '~/components/app-button';
import AddMarketModal from './add-market-modal';

/**
 * Event fired when the "Add market" button is clicked.
 *
 * @event gla_add_market_button_clicked
 */

/**
 * Component for the "Add market" button on the markets page, which opens a modal to add a new market.
 * @fires gla_add_market_button_clicked event when the button is clicked
 */
const AddMarketButton = () => {
	const [ isOpen, setIsOpen ] = useState( false );
	const {
		data: shippingRates,
		hasFinishedResolution: hasResolvedShippingRates,
	} = useShippingRates();
	const {
		hasFinishedResolution: hasResolvedShippingTimes,
		data: shippingTimes,
	} = useShippingTimes();
	const { targetAudience, loaded: hasResolvedTargetAudience } =
		useTargetAudienceFinalCountryCodes();

	const handleOpen = useCallback( () => setIsOpen( true ), [] );
	const handleClose = useCallback( () => setIsOpen( false ), [] );

	return (
		<>
			<AppButton
				variant="primary"
				onClick={ handleOpen }
				eventName="gla_add_market_button_clicked"
				loading={
					isOpen &&
					( ! hasResolvedShippingRates ||
						! hasResolvedShippingTimes ||
						! hasResolvedTargetAudience )
				}
			>
				{ __( 'Add market', 'google-listings-and-ads' ) }
			</AppButton>

			{ isOpen &&
				hasResolvedShippingRates &&
				hasResolvedShippingTimes &&
				hasResolvedTargetAudience && (
					<AddMarketModal
						shippingRates={ shippingRates }
						shippingTimes={ shippingTimes }
						targetAudience={ targetAudience }
						onRequestClose={ handleClose }
					/>
				) }
		</>
	);
};

export default AddMarketButton;
