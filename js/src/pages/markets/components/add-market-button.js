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
import usePrimaryMarketDetails from '../hooks/usePrimaryMarketDetails';
import AddMarketModal from './add-market-modal';

/**
 * Event fired when the "Add market" button is clicked.
 *
 * @event gla_add_market_button_clicked
 */

/**
 * Component for the "Add market" button on the markets page, which opens a modal to add a new market.
 *
 * Hidden when the primary market's audience covers only a single country, since
 * markets are derived from the primary market's countries and none remain to add.
 *
 * @fires gla_add_market_button_clicked event when the button is clicked
 */
const AddMarketButton = () => {
	const [ isOpen, setIsOpen ] = useState( false );
	const { targetAudience, loaded: hasResolvedTargetAudience } =
		useTargetAudienceFinalCountryCodes();
	const { settings } = useSettings();
	const {
		data: primaryMarket,
		hasFinishedResolution: hasResolvedPrimaryMarket,
	} = usePrimaryMarketDetails();

	const handleOpen = useCallback( () => setIsOpen( true ), [] );
	const handleClose = useCallback( () => setIsOpen( false ), [] );

	if ( ! hasResolvedPrimaryMarket || primaryMarket?.countries?.length <= 1 ) {
		return null;
	}

	return (
		<>
			<AppButton
				variant="primary"
				onClick={ handleOpen }
				eventName="gla_add_market_button_clicked"
				loading={
					isOpen && ( ! hasResolvedTargetAudience || ! settings )
				}
			>
				{ __( 'Add market', 'google-listings-and-ads' ) }
			</AppButton>

			{ isOpen && hasResolvedTargetAudience && (
				<AddMarketModal
					targetAudience={ targetAudience }
					onRequestClose={ handleClose }
					settings={ settings }
				/>
			) }
		</>
	);
};

export default AddMarketButton;
