/**
 * External dependencies
 */
import { createSlotFill } from '@wordpress/components';

/**
 * Internal dependencies
 */
import CampaignAssetsTour from './campaign-assets-tour';
import RebrandingTour from './rebranding-tour';
import GoogleTagGatewayTour from './google-tag-gateway-tour';
import useAppSelectDispatch from '~/hooks/useAppSelectDispatch';

export const { Fill, Slot } = createSlotFill( 'gla/Tours/Tour' );

const TOUR_COMPONENTS = {
	'rebranding-tour': RebrandingTour,
	'dashboard-feature--campaign-assets': CampaignAssetsTour,
	'google-tag-gateway-tour': GoogleTagGatewayTour,
};

const Tours = () => {
	const tours = useAppSelectDispatch( 'getTours' );

	const tourToRender = Object.keys( TOUR_COMPONENTS ).find(
		( id ) =>
			! ( tours && Object.prototype.hasOwnProperty.call( tours, id ) )
	);

	const TourComponent = TOUR_COMPONENTS[ tourToRender ];
	if ( ! TourComponent ) {
		return null;
	}

	return (
		<Fill>
			<TourComponent />
		</Fill>
	);
};

export default Tours;
