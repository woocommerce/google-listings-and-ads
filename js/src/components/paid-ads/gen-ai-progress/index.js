/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
// eslint-disable-next-line import/named, @woocommerce/dependency-group -- ProgressBar exists in @wordpress/components build output but isn't exported from index.ts (not part of the public API maybe).
import { ProgressBar } from '@wordpress/components';

/**
 * Internal dependencies
 */
import ProgressGraphics from '~/images/pmax-assets-improvements/gen-ai-progress.svg';
import SkipButton from './skip-button';
import './index.scss';

/**
 * Component to display the progress of Gen AI asset generation, including a progress bar and a skip button.
 *
 * @return {JSX.Element} The GenAIProgress component.
 */
const GenAIProgress = () => {
	return (
		<div className="gen-ai-progress">
			<img
				alt="Gen AI Progress"
				height={ 212 }
				src={ ProgressGraphics }
				width={ 212 }
			/>

			<div className="gen-ai-progress__text-content">
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

				<div className="gen-ai-progress__actions">
					<SkipButton />
				</div>
			</div>
		</div>
	);
};

export default GenAIProgress;
