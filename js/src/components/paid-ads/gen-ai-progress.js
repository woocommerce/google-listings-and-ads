/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex, FlexBlock, ProgressBar } from '@wordpress/components';

/**
 * Internal dependencies
 */
import ProgressGraphics from '~/images/pmax-assets-improvements/gen-ai-progress.svg';
import './gen-ai-progress.scss';

const GenAIProgress = () => {
	return (
		<div className="gen-ai-progress">
			<img
				src={ ProgressGraphics }
				alt="Gen AI Progress"
				width={ 212 }
				height={ 212 }
			/>
			<Flex direction="column" align="center" gap={ 2 }>
				<FlexBlock className="gen-ai-progress__text-content">
					<h2>
						{ __( 'Generating assets', 'google-listings-and-ads' ) }
					</h2>
					<ProgressBar className="gen-ai-progress__bar" />
					<p>
						{ __(
							'Google AI is analyzing your campaign’s URL to automatically generate your ad assets',
							'google-listings-and-ads'
						) }
					</p>
				</FlexBlock>
			</Flex>
		</div>
	);
};

export default GenAIProgress;
