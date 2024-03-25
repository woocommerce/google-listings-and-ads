/**
 * External dependencies
 */
import { TourKit } from '@woocommerce/components';
import { updateQueryString } from '@woocommerce/navigation';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useTour from '.~/hooks/useTour';
const TOUR_ID = 'AttributeMappingNavigationTour';

const AttributeMappingNavigationTour = () => {
	const { setTour, showTour } = useTour( TOUR_ID );

	const closeHandler = async ( _data, _step, origin ) => {
		await setTour( { id: TOUR_ID, checked: true } );

		if ( origin === 'done-btn' ) {
			updateQueryString( {}, '/google/attribute-mapping', {} );
		}
	};

	const config = {
		steps: [
			{
				referenceElements: {
					desktop: '.app-tab-nav #attribute-mapping',
				},
				meta: {
					heading: __(
						'Introducing Attributes 🎉',
						'google-listings-and-ads'
					),
					descriptions: {
						desktop: __(
							'We just launched a new way for you to easily submit product data to your Google product feed.',
							'google-listings-and-ads'
						),
					},
					primaryButton: {
						text: __( 'Explore', 'google-listings-and-ads' ),
					},
				},
			},
		],
		options: {
			effects: { overlay: false },
		},
		placement: 'bottom',
		closeHandler,
	};

	return showTour ? <TourKit config={ config } /> : null;
};

export default AttributeMappingNavigationTour;
