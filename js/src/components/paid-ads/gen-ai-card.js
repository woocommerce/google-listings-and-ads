/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon, check } from '@wordpress/icons';
import {
	Flex,
	FlexBlock,
	FlexItem,
	CardBody,
	Notice,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import Section from '~/components/section';
import useGoogleAdsAccount from '~/hooks/useGoogleAdsAccount';
import genAIImageURL from '~/images/pmax-assets-improvements/gen-ai.svg';
import './gen-ai-card.scss';

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
	const queryArgs = {};

	if ( googleAdsAccount?.ocid ) {
		queryArgs.ocid = googleAdsAccount.ocid;
	} else if ( googleAdsAccount?.id ) {
		queryArgs.ecid = googleAdsAccount.id;
	}

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
							<div>
								<Section.Card.Title
									direction={ [ 'column-reverse', 'row' ] }
								>
									{ __(
										'Review Your AI Suggestions',
										'google-listings-and-ads'
									) }
								</Section.Card.Title>
								<div>
									{ __(
										'Google AI analyzed your campaign’s URL to automatically generate your ad assets. Please review the suggested text and images below to ensure they align with your brand.',
										'google-listings-and-ads'
									) }
								</div>
							</div>

							<Notice status="success" isDismissible={ false }>
								<Icon
									icon={ check }
									width={ 24 }
									height={ 24 }
								/>
								<p>
									{ __(
										'Text assets were auto-populated with Google AI',
										'google-listings-and-ads'
									) }
								</p>
							</Notice>
						</Flex>
					</FlexBlock>
					<FlexItem className="gla-gen-ai-card__image-block">
						<img
							src={ genAIImageURL }
							alt={ __(
								"Google's Gen AI illustration",
								'google-listings-and-ads'
							) }
							width="92"
							height="90"
						/>
					</FlexItem>
				</Flex>
			</CardBody>
		</Section.Card>
	);
};

export default GenAICard;
