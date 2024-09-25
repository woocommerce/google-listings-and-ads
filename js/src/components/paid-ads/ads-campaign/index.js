/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	createInterpolateElement,
	useState,
	useEffect,
} from '@wordpress/element';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '.~/components/adaptive-form';
import StepContent from '.~/components/stepper/step-content';
import StepContentHeader from '.~/components/stepper/step-content-header';
import StepContentFooter from '.~/components/stepper/step-content-footer';
import AppDocumentationLink from '.~/components/app-documentation-link';
import AppButton from '.~/components/app-button';
import FaqsSection from '../faqs-section';
import PaidAdsFeaturesSection from './paid-ads-features-section';
import PaidAdsSetupSections from './paid-ads-setup-sections';
import SkipButton from './skip-button';
import useTargetAudienceFinalCountryCodes from '.~/hooks/useTargetAudienceFinalCountryCodes';
import { ACTION_SKIP, ACTION_COMPLETE } from './constants';

/**
 * @typedef {import('.~/data/actions').Campaign} Campaign
 */

/**
 * Renders the container of the form content for campaign management.
 *
 * Please note that this component relies on an CampaignAssetsForm's context and custom adapter,
 * so it expects a `CampaignAssetsForm` to existing in its parents.
 *
 * @fires gla_documentation_link_click with `{ context: 'create-ads' | 'edit-ads' | 'setup-ads', link_id: 'see-what-ads-look-like', href: 'https://support.google.com/google-ads/answer/6275294' }`
 * @param {Object} props React props.
 * @param {Campaign} [props.campaign] Campaign data to be edited. If not provided, this component will show campaign creation UI.
 * @param {string} props.headerTitle The title of the step.
 * @param {string} [props.headerDescription] The description of the step.
 * @param {() => void} props.onContinue Callback called once continue button is clicked.
 * @param {() => void} [props.onSkip] Callback called once skip button is clicked.
 * @param {boolean} [props.hasError=false] Whether there's an error to reset the completing state.
 * @param {boolean} [props.isOnboardingFlow=false] Whether this component is used in onboarding flow.
 * @param {'create-ads'|'edit-ads'|'setup-ads'} props.trackingContext A context indicating which page this component is used on. This will be the value of `context` in the track event properties.
 */
export default function AdsCampaign( {
	campaign,
	headerTitle,
	headerDescription,
	onContinue,
	onSkip = noop,
	hasError = false,
	isOnboardingFlow = false,
	trackingContext,
} ) {
	const formContext = useAdaptiveFormContext();
	const { data: countryCodes } = useTargetAudienceFinalCountryCodes();
	const { isValidForm, setValue } = formContext;
	const [ completing, setCompleting ] = useState( null );
	const [ paidAds, setPaidAds ] = useState( {} );

	useEffect( () => {
		if ( hasError ) {
			setCompleting( null );
		}
	}, [ hasError ] );

	const handleOnStatesReceived = ( paidAdsValues ) => {
		setPaidAds( paidAdsValues );

		const { amount } = paidAdsValues;
		setValue( 'amount', amount );
	};

	const handleSkipCreateAds = () => {
		setCompleting( ACTION_SKIP );

		onSkip( paidAds );
	};

	const handleCompleteClick = ( event ) => {
		setCompleting( event.target.dataset.action );

		onContinue( paidAds );
	};

	// The status check of Google Ads account connection is included in `paidAds.isReady`,
	// because when there is no connected account, it will disable the budget section and set the `amount` to `undefined`.
	const disabledComplete =
		completing === ACTION_SKIP || ! paidAds.isReady || ! isValidForm;

	let continueButtonProps = {
		text: __( 'Continue', 'google-listings-and-ads' ),
	};

	if ( isOnboardingFlow ) {
		continueButtonProps = {
			'data-action': ACTION_COMPLETE,
			text: __( 'Complete setup', 'google-listings-and-ads' ),
			eventName: 'gla_onboarding_complete_with_paid_ads_button_click',
			eventProps: {
				budget: paidAds.amount,
				audiences: countryCodes?.join( ',' ),
			},
		};
	}

	const description =
		headerDescription ||
		createInterpolateElement(
			__(
				'Paid Performance Max campaigns are automatically optimized for you by Google. <link>See what your ads will look like.</link>',
				'google-listings-and-ads'
			),
			{
				link: (
					<AppDocumentationLink
						context={ trackingContext }
						linkId="see-what-ads-look-like"
						href="https://support.google.com/google-ads/answer/6275294"
					/>
				),
			}
		);

	return (
		<StepContent>
			<StepContentHeader
				title={ headerTitle }
				description={ description }
			/>

			{ isOnboardingFlow && <PaidAdsFeaturesSection /> }

			<PaidAdsSetupSections
				onStatesReceived={ handleOnStatesReceived }
				campaign={ campaign }
				countryCodes={ countryCodes }
			/>

			<FaqsSection />

			<StepContentFooter>
				{ isOnboardingFlow && (
					<SkipButton
						text={ __(
							'Skip paid ads creation',
							'google-listings-and-ads'
						) }
						onSkipCreatePaidAds={ handleSkipCreateAds }
						completing={ completing }
						paidAds={ paidAds }
					/>
				) }

				<AppButton
					isPrimary
					disabled={ disabledComplete }
					onClick={ handleCompleteClick }
					loading={ completing === ACTION_COMPLETE }
					{ ...continueButtonProps }
				/>
			</StepContentFooter>
		</StepContent>
	);
}
