/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex } from '@wordpress/components';

/**
 * Internal dependencies
 */
import Section from '~/components/section';
import { SetupEnhancedConversions } from './enhanced-conversions';
import SetupGoogleTagGateway from './setup-google-tag-gateway';

const ImproveDataStrength = () => {
	return (
		<Section
			title={ __( 'Improve data strength', 'google-listings-and-ads' ) }
			description={
				<div>
					<p>
						{ __(
							'Boost the accuracy of your Google Ads tracking with privacy-focused data tools.',
							'google-listings-and-ads'
						) }
					</p>
				</div>
			}
		>
			<Section.Card>
				<Section.Card.Body>
					<Flex direction="column" gap={ 3 }>
						<SetupGoogleTagGateway />
						<SetupEnhancedConversions />
					</Flex>
				</Section.Card.Body>
			</Section.Card>
		</Section>
	);
};

export default ImproveDataStrength;
