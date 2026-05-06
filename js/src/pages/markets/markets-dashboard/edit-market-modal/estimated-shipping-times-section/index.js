/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Flex, FlexBlock } from '@wordpress/components';

/**
 * Internal dependencies
 */
import MinMaxShippingTimes from '~/components/free-listings/configure-product-listings/shipping-time-setup/min-max-shipping-times';
import Section from '~/components/section';
import Subsection from '~/components/subsection';
import VerticalGapLayout from '~/components/vertical-gap-layout';
import './index.scss';

const EstimatedShippingTimesSection = () => {
	const [ minTime, setMinTime ] = useState( 0 );
	const [ maxTime, setMaxTime ] = useState( 3 );

	const handleBlur = ( numberValue, field ) => {
		if ( field === 'time' && minTime !== numberValue ) {
			setMinTime( numberValue );
		} else if ( field === 'maxTime' && maxTime !== numberValue ) {
			setMaxTime( numberValue );
		}
	};

	const handleIncrement = ( numberValue, field ) => {
		if ( field === 'time' ) {
			setMinTime( numberValue );
		} else if ( field === 'maxTime' ) {
			setMaxTime( numberValue );
		}
	};

	return (
		<Section.Card className="gla-edit-market-modal__estimated-times">
			<Section.Card.Body>
				<Section.Card.Title>
					{ __(
						'Estimated shipping times',
						'google-listings-and-ads'
					) }
				</Section.Card.Title>
				<VerticalGapLayout size="large">
					<Flex className="gla-countries-time-input-container">
						<FlexBlock>
							<MinMaxShippingTimes
								time={ minTime }
								maxTime={ maxTime }
								handleBlur={ handleBlur }
								handleIncrement={ handleIncrement }
							/>
						</FlexBlock>
					</Flex>
					<Subsection.HelperText>
						{ __(
							'Delivery times apply per country, regardless of language or currency.',
							'google-listings-and-ads'
						) }
					</Subsection.HelperText>
				</VerticalGapLayout>
			</Section.Card.Body>
		</Section.Card>
	);
};

export default EstimatedShippingTimesSection;
