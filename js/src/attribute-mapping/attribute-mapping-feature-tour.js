/**
 * External dependencies
 */
import { TourKit } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useTour from '.~/hooks/useTour';
const TOUR_ID = 'AttributeMappingFeatureTour';

const AttributeMappingFeatureTour = () => {
	const { setTour, showTour } = useTour( TOUR_ID );

	const closeHandler = async () => {
		await setTour( { id: TOUR_ID, checked: true } );
	};

	const config = {
		steps: [
			{
				referenceElements: {
					desktop: '.gla-attribute-mapping__table',
				},
				meta: {
					heading: __(
						'Manage product attributes using rules',
						'google-listings-and-ads'
					),
					descriptions: {
						desktop: (
							<div>
								<p>
									{ __(
										'Set up rules to automatically populate your Google product feed whenever you make updates to your store.',
										'google-listings-and-ads'
									) }
								</p>
								<p>
									{ __(
										'To get started, click on Create attribute rule button.',
										'google-listings-and-ads'
									) }
								</p>
							</div>
						),
					},
					primaryButton: {
						text: __( 'Got it', 'google-listings-and-ads' ),
					},
				},
			},
		],
		options: {
			effects: { overlay: true },
		},
		placement: 'left',
		closeHandler,
	};

	return showTour ? <TourKit config={ config } /> : null;
};

export default AttributeMappingFeatureTour;
