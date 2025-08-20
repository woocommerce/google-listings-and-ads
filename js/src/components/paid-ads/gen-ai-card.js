/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import { external as externalIcon } from '@wordpress/icons';
import { createInterpolateElement } from '@wordpress/element';
import { Icon, Flex, FlexBlock, CardBody } from '@wordpress/components';

/**
 * Internal dependencies
 */
import Badge from '~/components/badge';
import Section from '~/components/section';
import AppButton from '~/components/app-button';
import useGoogleAdsAccount from '~/hooks/useGoogleAdsAccount';
import AppDocumentationLink from '~/components/app-documentation-link';
import genAIImageURL from '~/images/pmax-assets-improvements/gen-ai.svg';
import { GOOGLE_ADS_ACCOUNT_STATUS } from '~/constants';
import './gen-ai-card.scss';

const { CONNECTED, INCOMPLETE } = GOOGLE_ADS_ACCOUNT_STATUS;

/**
 * GenAICard component displays a promotional card for Google AI-powered asset generation
 * within Performance Max campaigns. It provides information about the feature, a link to
 * documentation, and a button to generate assets using GenAI, which is enabled only if the
 * Google Ads account is connected.
 *
 * @return {JSX.Element} The rendered GenAICard component.
 */
const GenAICard = () => {
	const { googleAdsAccount } = useGoogleAdsAccount();
	const hasAdsAccount = [ CONNECTED, INCOMPLETE ].includes(
		googleAdsAccount?.status
	);
	const queryArgs = {};

	if ( googleAdsAccount?.ocid ) {
		queryArgs.ocid = googleAdsAccount.ocid;
	} else if ( googleAdsAccount?.id ) {
		queryArgs.ecid = googleAdsAccount.id;
	}

	const recommendationsURL = addQueryArgs(
		'https://ads.google.com/aw/recommendations',
		queryArgs
	);

	return (
		<Section.Card className="gla-gen-ai-card">
			<CardBody size="large">
				<Flex
					align="center"
					gap={ 6 }
					direction={ [ 'column', 'row' ] }
					className="gla-gen-ai-card__wrapper"
				>
					<FlexBlock>
						<Flex direction="column" gap={ 4 } align="start">
							<Badge>
								{ __(
									'Now available',
									'google-listings-and-ads'
								) }
							</Badge>

							<div>
								<Section.Card.Title>
									{ __(
										'You can use Google AI to help build Performance Max assets with a few clicks.',
										'google-listings-and-ads'
									) }
								</Section.Card.Title>
								<div>
									{ createInterpolateElement(
										__(
											'Starting with your website, Google AI will understand what you’re advertising and can generate or suggest text, image, logo, and video assets for you. <link>Learn more</link>',
											'google-listings-and-ads'
										),
										{
											link: (
												<AppDocumentationLink
													href="https://support.google.com/google-ads/answer/14150602"
													context="pmax-assets-improvements"
													linkId="pmax-gen-ai-card"
												/>
											),
										}
									) }
								</div>
							</div>

							<AppButton
								icon={ <Icon icon={ externalIcon } /> }
								iconPosition="right"
								href={ recommendationsURL }
								disabled={ ! hasAdsAccount }
								target="_blank"
								isSecondary
							>
								{ __(
									'Generate assets with GenAI',
									'google-listings-and-ads'
								) }
							</AppButton>
						</Flex>
					</FlexBlock>
					<FlexBlock>
						<img
							src={ genAIImageURL }
							alt={ __(
								'Drawing of a person who successfully launched a campaign',
								'google-listings-and-ads'
							) }
							width="212"
							height="212"
						/>
					</FlexBlock>
				</Flex>
			</CardBody>
		</Section.Card>
	);
};

export default GenAICard;
