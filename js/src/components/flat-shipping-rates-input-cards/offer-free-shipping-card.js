/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import AppRadioContentControl from '../app-radio-content-control';
import VerticalGapLayout from '.~/components/vertical-gap-layout';

const OfferFreeShippingCard = ( props ) => {
	const { value, onChange } = props;

	const handleChange = ( newValue ) => {
		onChange( newValue === 'yes' );
	};

	return (
		<Section.Card>
			<Section.Card.Body>
				<Section.Card.Title>
					{ __(
						'I offer free shipping for orders over a certain price',
						'google-listings-and-ads'
					) }
				</Section.Card.Title>
				<VerticalGapLayout size="large">
					<AppRadioContentControl
						label={ __( 'Yes', 'google-listings-and-ads' ) }
						value="yes"
						selected={ value === true && 'yes' }
						onChange={ handleChange }
					/>
					<AppRadioContentControl
						label={ __( 'No', 'google-listings-and-ads' ) }
						value="no"
						selected={ value === false && 'no' }
						onChange={ handleChange }
					/>
				</VerticalGapLayout>
			</Section.Card.Body>
		</Section.Card>
	);
};

export default OfferFreeShippingCard;
