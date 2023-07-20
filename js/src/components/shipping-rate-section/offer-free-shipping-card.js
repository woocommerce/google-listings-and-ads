/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import AppRadioContentControl from '.~/components/app-radio-content-control';
import VerticalGapLayout from '.~/components/vertical-gap-layout';

/**
 * Renders a Card UI with options to choose whether offer free shipping
 * for orders over a certain price.
 *
 * @param {Object} props React props.
 * @param {boolean} props.value The value of whether offer free shipping.
 * @param {JSX.Element} [props.helper] Helper content to be rendered at the bottom of the card body.
 * @param {(nextValue: boolean) => void} props.onChange Callback called with the next value of the selected option.
 */
const OfferFreeShippingCard = ( { value, helper, onChange } ) => {
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
				{ helper }
			</Section.Card.Body>
		</Section.Card>
	);
};

export default OfferFreeShippingCard;
