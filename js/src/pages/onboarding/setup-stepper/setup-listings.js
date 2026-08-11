/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Notice } from '@wordpress/components';

/**
 * Internal dependencies
 */
import Section from '~/components/section';
import StepContent from '~/components/stepper/step-content';
import StepContentActions from '~/components/stepper/step-content-actions';
import StepContentFooter from '~/components/stepper/step-content-footer';
import SetupFreeListings from '~/components/free-listings/setup-free-listings';
import Hero from './hero';

/**
 * Renders the onboarding step for setting up the product listings.
 *
 * @param {Object} props React props to be forwarded to `SetupFreeListings`.
 */
export default function SetupListings( props ) {
	return (
		<>
			<Hero />
			<StepContent>
				<SetupFreeListings { ...props } />

				<Section>
					<Notice isDismissible={ false }>
						{ __(
							'You can set up additional market feeds with custom shipping per country in Settings after completing this setup — including support for multiple languages and currencies.',
							'google-listings-and-ads'
						) }
					</Notice>
				</Section>

				<StepContentFooter>
					<StepContentActions>
						<SetupFreeListings.SubmitButton />
					</StepContentActions>
				</StepContentFooter>
			</StepContent>
		</>
	);
}
