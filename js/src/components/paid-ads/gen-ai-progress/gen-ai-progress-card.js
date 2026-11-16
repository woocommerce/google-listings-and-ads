/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex, FlexBlock, FlexItem, ProgressBar } from '@wordpress/components';

/**
 * Internal dependencies
 */
import Section from '~/components/section';
import GenAIIcon from '~/images/pmax-assets-improvements/gen-ai-progress-card.svg';
import SkipButton from './skip-button';
import './gen-ai-progress-card.scss';

/**
 * Compact card that shows the progress of Gen AI asset generation,
 * with a progress bar and a skip button that is only visible while generating.
 *
 * @return {JSX.Element} The GenAIProgressCard component.
 */
const GenAIProgressCard = () => {
	return (
		<Section.Card className="gen-ai-progress-card">
			<Section.Card.Body>
				<Flex align="flex-start" gap={ 4 }>
					<FlexItem>
						<img
							src={ GenAIIcon }
							alt=""
							width={ 48 }
							height={ 48 }
						/>
					</FlexItem>

					<FlexBlock>
						<Flex direction="column" align="stretch" gap={ 4 }>
							<div>
								<Section.Card.Title>
									{ __(
										'Google AI suggestions',
										'google-listings-and-ads'
									) }
								</Section.Card.Title>
								<p className="gen-ai-progress-card__text">
									{ __(
										'Google AI is analyzing your campaign’s URL to automatically generate your ad assets',
										'google-listings-and-ads'
									) }
								</p>
							</div>

							<ProgressBar className="gen-ai-progress-card__bar" />

							<Flex justify="flex-end">
								<SkipButton />
							</Flex>
						</Flex>
					</FlexBlock>
				</Flex>
			</Section.Card.Body>
		</Section.Card>
	);
};

export default GenAIProgressCard;
