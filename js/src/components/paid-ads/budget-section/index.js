/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useRef, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import getMonthlyMaxEstimated from './getMonthlyMaxEstimated';
import './index.scss';
import BudgetRecommendation from './budget-recommendation';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import AppInputPriceControl from '.~/components/app-input-price-control';
import AppSpinner from '.~/components/app-spinner';
import useFetchBudgetRecommendation from '.~/hooks/useFetchBudgetRecommendation';
import clientSession from './clientSession';

/**
 * @typedef {import('.~/data/actions').CountryCode} CountryCode
 */

const nonInteractableProps = {
	noPointerEvents: true,
	readOnly: true,
	tabIndex: -1,
};

/**
 * Renders <Section> and <Section.Card> UI with campaign budget inputs.
 *
 * @param {Object} props React props.
 * @param {Object} props.formProps Form props forwarded from `Form` component.
 * @param {Array<CountryCode>|undefined} props.countryCodes Country codes to fetch budget recommendations for.
 * @param {boolean} [props.disabled=false] Whether display the Card in disabled style.
 * @param {JSX.Element} [props.children] Extra content to be rendered under the card of budget inputs.
 * @param {'create-ads'|'edit-ads'|'setup-ads'|'setup-mc'} props.context A context indicating which page this component is used on.
 */
const BudgetSection = ( {
	formProps,
	countryCodes,
	disabled = false,
	children,
	context,
} ) => {
	const initialAmountRef = useRef( null );
	const { getInputProps, values, setValue } = formProps;
	const { amount } = values;
	const { googleAdsAccount } = useGoogleAdsAccount();
	const {
		data: budgetRecommendationData,
		highestDailyBudget,
		highestDailyBudgetCountryCode,
		hasFinishedResolution,
	} = useFetchBudgetRecommendation( countryCodes );
	const monthlyMaxEstimated = getMonthlyMaxEstimated( amount );
	// Display the currency code that will be used by Google Ads, but still use the store's currency formatting settings.
	const currency = googleAdsAccount?.currency;

	useEffect( () => {
		// Load the amount from client session during the onboarding flow only.
		if (
			context !== 'setup-mc' ||
			! hasFinishedResolution ||
			! values.amount
		) {
			return;
		}

		if ( values.amount >= highestDailyBudget ) {
			clientSession.setCampaign( values );
		}
	}, [ values, highestDailyBudget, context, hasFinishedResolution ] );

	if ( ! initialAmountRef.current && ! amount && hasFinishedResolution ) {
		let clientSessionAmount = 0;
		if ( context === 'setup-mc' ) {
			( { amount: clientSessionAmount } = clientSession.getCampaign() );
		}

		initialAmountRef.current = true;
		setValue(
			'amount',
			Math.max( clientSessionAmount, highestDailyBudget )
		);
	}

	return (
		<div className="gla-budget-section">
			<Section
				disabled={ disabled }
				title={ __( 'Set your budget', 'google-listings-and-ads' ) }
				description={
					<p>
						{ __(
							'With Performance Max campaigns, you can set your own budget and Google’s Smart Bidding technology will serve the most appropriate ad, with the optimal bid, to maximize campaign performance. You only pay when people click on your ads, and you can start or stop your campaign whenever you want.',
							'google-listings-and-ads'
						) }
					</p>
				}
			>
				<Section.Card>
					<Section.Card.Body className="gla-budget-section__card-body">
						{ hasFinishedResolution ? (
							<>
								<div className="gla-budget-section__card-body__cost">
									<AppInputPriceControl
										label={ __(
											'Daily average cost',
											'google-listings-and-ads'
										) }
										suffix={ currency }
										{ ...getInputProps( 'amount' ) }
										{ ...( disabled &&
											nonInteractableProps ) }
									/>
									<AppInputPriceControl
										disabled
										label={ __(
											'Monthly max, estimated',
											'google-listings-and-ads'
										) }
										suffix={ currency }
										value={ monthlyMaxEstimated }
									/>
								</div>
								{ countryCodes?.length > 0 && (
									<BudgetRecommendation
										dailyAverageCost={ amount }
										highestDailyBudget={
											highestDailyBudget
										}
										highestDailyBudgetCountryCode={
											highestDailyBudgetCountryCode
										}
										budgetRecommendationData={
											budgetRecommendationData
										}
									/>
								) }
							</>
						) : (
							<AppSpinner />
						) }
					</Section.Card.Body>
				</Section.Card>
				{ children }
			</Section>
		</div>
	);
};

export default BudgetSection;
