/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import GridiconInfoOutline from 'gridicons/dist/info-outline';

/**
 * Internal dependencies
 */
import useCountryKeyNameMap from '.~/hooks/useCountryKeyNameMap';
import useFetchBudgetRecommendationEffect from './useFetchBudgetRecommendationEffect';
import './index.scss';

/*
 * If a merchant selects more than one country, the budget recommendation
 * takes the highest country out from the selected countries.
 * When looking for the highest one, it should only consider the `daily_budget_high` value.
 *
 * For example, a merchant selected Brunei (5-20 USD) and Croatia (10-15 USD),
 * then the budget recommendation should be (5-20 USD).
 */
function getHighestBudget( recommendations ) {
	return recommendations.reduce( ( defender, challenger ) => {
		if ( challenger.daily_budget_high > defender.daily_budget_high ) {
			return challenger;
		}
		return defender;
	} );
}

function toRecommendationRange( isMultiple, ...values ) {
	const conversionMap = { strong: <strong /> };
	const template = isMultiple
		? // translators: it's a range of recommended budget amount. 1: the low value of the range, 2: the high value of the range, 3: the currency of amount.
		  __(
				'Most merchants targeting similar countries set a daily budget of <strong>%1$f to %2$f %3$s</strong> for approximately 10 conversions a week.',
				'google-listings-and-ads'
		  )
		: // translators: it's a range of recommended budget amount. 1: the low value of the range, 2: the high value of the range, 3: the currency of amount, 4: a country name selected by the merchant.
		  __(
				'Most merchants targeting <strong>%4$s</strong> set a daily budget of <strong>%1$f to %2$f %3$s</strong> for approximately 10 conversions a week.',
				'google-listings-and-ads'
		  );

	return createInterpolateElement(
		sprintf( template, ...values ),
		conversionMap
	);
}

const BudgetRecommendation = ( props ) => {
	const { countryCodes, dailyAverageCost = Infinity } = props;
	const { data } = useFetchBudgetRecommendationEffect( countryCodes );
	const map = useCountryKeyNameMap();

	if ( ! data ) {
		return null;
	}

	const { currency, recommendations } = data;
	const {
		daily_budget_low: dailyBudgetLow,
		daily_budget_high: dailyBudgetHigh,
		country,
	} = getHighestBudget( recommendations );

	const countryName = map[ country ];
	const recommendationRange = toRecommendationRange(
		recommendations.length > 1,
		dailyBudgetLow,
		dailyBudgetHigh,
		currency,
		countryName
	);

	const showLowerBudgetNotice = dailyAverageCost < dailyBudgetLow;

	return (
		<div className="gla-budget-recommendation">
			<div className="gla-budget-recommendation__recommendation">
				<GridiconInfoOutline />
				<div>{ recommendationRange }</div>
			</div>
			{ showLowerBudgetNotice && (
				<div className="gla-budget-recommendation__low-budget">
					<GridiconInfoOutline />
					<div>
						{ __(
							'With a budget lower than your competitor range, your campaign may not get noticeable results.',
							'google-listings-and-ads'
						) }
					</div>
				</div>
			) }
		</div>
	);
};

export default BudgetRecommendation;
