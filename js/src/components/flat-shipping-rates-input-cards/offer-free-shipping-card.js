/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import Subsection from '.~/wcdl/subsection';
import AppRadioContentControl from '../app-radio-content-control';
import VerticalGapLayout from '.~/components/vertical-gap-layout';

const OfferFreeShippingCard = ( props ) => {
	const { value, onChange } = props;

	return (
		<Section.Card>
			<Section.Card.Body>
				<VerticalGapLayout size="large">
					<Subsection.Title>
						{ __(
							'I offer free shipping for orders over a certain price',
							'google-listings-and-ads'
						) }
					</Subsection.Title>
					<AppRadioContentControl
						label={ __( 'Yes', 'google-listings-and-ads' ) }
						value="yes"
						selected={ value }
						onChange={ onChange }
					/>
					<AppRadioContentControl
						label={ __( 'No', 'google-listings-and-ads' ) }
						value="no"
						selected={ value }
						onChange={ onChange }
					/>
				</VerticalGapLayout>
			</Section.Card.Body>
		</Section.Card>
	);
};

export default OfferFreeShippingCard;
