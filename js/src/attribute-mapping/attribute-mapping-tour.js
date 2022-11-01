/**
 * External dependencies
 */
import { TourKit } from '@woocommerce/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

const AttributeMappingTour = () => {
	const [ showTour, setShowTour ] = useState( true );
	const config = {
		steps: [
			{
				referenceElements: {
					desktop: '.gla-attribute-mapping__table',
				},
				meta: {
					heading: 'Test',
					descriptions: {
						desktop: 'Lorem ipsum dolor sit amet.',
					},
					primaryButton: {
						text: __( 'Got it', 'google-listings-and-ads' ),
					},
				},
			},
		],
		placement: 'left',
		closeHandler: () => setShowTour( false ),
	};

	return showTour && <TourKit config={ config } placement={ 'left' } />;
};

export default AttributeMappingTour;
