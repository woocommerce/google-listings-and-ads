/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useRef, useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import getMonthlyMaxEstimated from './getMonthlyMaxEstimated';
import './index.scss';
import BudgetRecommendation from './budget-recommendation';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import AppInputPriceControl from '.~/components/app-input-price-control';

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
 * @param {string} props.country Country code.
 * @param {Array<CountryCode>} props.countryCodes Country codes to fetch budget recommendations for.
 * @param {number} props.dailyBudget Daily budget.
 * @param {boolean} [props.disabled=false] Whether display the Card in disabled style.
 * @param {boolean} [props.isMultiple=false] Whether the campaign is targeting multiple countries.
 * @param {JSX.Element} [props.children] Extra content to be rendered under the card of budget inputs.
 */
const BudgetSection = ( {
	formProps,
	country,
	countryCodes,
	dailyBudget,
	disabled = false,
	children,
	isMultiple,
} ) => {
	const { getInputProps, setValue, values } = formProps;
	const { amount } = values;
	const { googleAdsAccount } = useGoogleAdsAccount();
	const monthlyMaxEstimated = getMonthlyMaxEstimated( amount );
	// Display the currency code that will be used by Google Ads, but still use the store's currency formatting settings.
	const currency = googleAdsAccount?.currency;

	// Wrapping `useRef` is because since WC 6.9, the reference of `setValue` may be changed
	// after calling itself and further leads to an infinite re-rendering loop if used in a
	// `useEffect`.
	const setValueRef = useRef();
	setValueRef.current = setValue;

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
						<div className="gla-budget-section__card-body__cost">
							<AppInputPriceControl
								label={ __(
									'Daily average cost',
									'google-listings-and-ads'
								) }
								suffix={ currency }
								{ ...getInputProps( 'amount' ) }
								{ ...( disabled && nonInteractableProps ) }
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
						<BudgetRecommendation
							countryCodes={ countryCodes }
							dailyAverageCost={ amount }
							dailyBudget={ dailyBudget }
							country={ country }
							currency={ currency }
							isMultiple={ isMultiple }
						/>
					</Section.Card.Body>
				</Section.Card>
				{ children }
			</Section>
		</div>
	);
};

export default BudgetSection;
