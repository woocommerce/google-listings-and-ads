/**
 * External dependencies
 */
import { TourKit } from '@woocommerce/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

const AttributeMappingNavigationTour = () => {
	const [ showTour, setShowTour ] = useState( true );
	const config = {
		steps: [
			{
				referenceElements: {
					desktop: '.gla-attribute-mapping__table',
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
		placement: 'bottom',
		closeHandler: () => setShowTour( false ),
	};

	return showTour && <TourKit config={ config } />;
};

export default AttributeMappingNavigationTour;
