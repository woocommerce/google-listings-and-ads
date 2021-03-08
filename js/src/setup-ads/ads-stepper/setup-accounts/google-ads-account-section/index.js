/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '../../../../wcdl/section';
import TitleButtonLayout from '../../../../components/title-button-layout';

const GoogleAdsAccountSection = () => {
	return (
		<Section
			title={ __( 'Google Ads account', 'google-listings-and-ads' ) }
			description={
				<p>
					{ __(
						'Any campaigns created through this app will appear in your Google Ads account. You will be billed directly through Google.',
						'google-listings-and-ads'
					) }
				</p>
			}
		>
			<Section.Card>
				<Section.Card.Body>
					<TitleButtonLayout
						title={ __(
							'Create your Google Ads account',
							'google-listings-and-ads'
						) }
						button={
							<Button isSecondary>
								{ __(
									'Create account',
									'google-listings-and-ads'
								) }
							</Button>
						}
					/>
				</Section.Card.Body>
			</Section.Card>
		</Section>
	);
};

export default GoogleAdsAccountSection;
