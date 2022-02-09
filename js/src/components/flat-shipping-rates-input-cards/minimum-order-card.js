/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import Subsection from '.~/wcdl/subsection';
import VerticalGapLayout from '.~/components/vertical-gap-layout';

const MinimumOrderCard = () => {
	return (
		<Section.Card>
			<Section.Card.Body>
				<VerticalGapLayout size="large">
					<Subsection.Title>
						{ __(
							'Minimum order to qualify for free shipping',
							'google-listings-and-ads'
						) }
					</Subsection.Title>
				</VerticalGapLayout>
			</Section.Card.Body>
		</Section.Card>
	);
};

export default MinimumOrderCard;
