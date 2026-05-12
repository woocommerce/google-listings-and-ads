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
 * Owns the open / close state for `AddMarketModal`. The modal itself is a
 * placeholder today; the follow-up task will replace its body with a real
 * country / shipping form and a save handler.
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
				loading={
					! hasResolvedShippingRates ||
					! hasResolvedShippingTimes ||
					! hasResolvedTargetAudience
				}
			>
				{ __( 'Add market', 'google-listings-and-ads' ) }
			</AppButton>

			{ isOpen && (
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
