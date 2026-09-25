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
import './index.scss';

/**
 * Component to display the progress of Gen AI asset generation, including a progress bar and optional description and actions.
 *
 * @param {Object} props React props.
 * @param {string} [props.title] The heading shown above the progress bar.
 * @param {string} [props.description] The text shown below the progress bar.
 * @param {JSX.Element} [props.actions] The actions shown below the description.
 * @return {JSX.Element} The GenAIProgress component.
 */
const GenAIProgress = ( {
	title = __( 'Generating assets', 'google-listings-and-ads' ),
	description,
	actions,
} ) => {
	return (
		<div className="gen-ai-progress">
			<img
				src={ ProgressGraphics }
				alt="Gen AI Progress"
				width={ 212 }
				height={ 212 }
			/>

			<div className="gen-ai-progress__text-content">
				<h2>{ title }</h2>

				<ProgressBar className="gen-ai-progress__bar" />

				{ description && <p>{ description }</p> }

				{ actions && (
					<div className="gen-ai-progress__actions">{ actions }</div>
				) }
			</div>
		</div>
	);
};

export default GenAIProgress;
