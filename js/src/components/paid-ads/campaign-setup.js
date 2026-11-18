/**
 * External dependencies
 */
import { Flex } from '@wordpress/components';

/**
 * Internal dependencies
 */
import StepContent from '~/components/stepper/step-content';
import StepContentActions from '~/components/stepper/step-content-actions';
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import Faqs from './asset-group/faqs';
import './campaign-setup.scss';

/**
 * @typedef {import('~/components/adaptive-form/adaptive-form-context').AdaptiveFormContext} AdaptiveFormContext
 */

/**
 * Renders the first step of campaign management: the given sections, the "Continue" button and the FAQs.
 *
 * It expects a `CampaignAssetsForm` to exist in its parents.
 *
 * @param {Object} props React props.
 * @param {JSX.Element} props.children The sections of the step.
 * @param {(formContext: AdaptiveFormContext) => JSX.Element | JSX.Element} [props.continueButton] A React element or function to render the "Continue" button. If a function is passed, it receives the form context and returns the button element.
 * @return {JSX.Element} The rendered component.
 */
export default function CampaignSetup( { children, continueButton } ) {
	const formContext = useAdaptiveFormContext();

	return (
		<StepContent variant="narrow">
			{ children }

			<Flex
				direction="column"
				gap="8"
				className="gla-campaign-setup-footer"
			>
				<StepContentActions>
					{ typeof continueButton === 'function'
						? continueButton( formContext )
						: continueButton }
				</StepContentActions>
				<Faqs />
			</Flex>
		</StepContent>
	);
}
