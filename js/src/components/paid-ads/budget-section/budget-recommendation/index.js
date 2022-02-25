/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
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

const BudgetRecommendation = ( props ) => {
	const { countryCodes, dailyAverageCost } = props;
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
	const [ recommendation ] = recommendations;
	const countryName = map[ country ];

	const showLowerBudgetNotice =
		dailyAverageCost !== '' &&
		Number( dailyAverageCost ) < Number( recommendation.daily_budget_low );

	return (
		<div className="gla-budget-recommendation">
			<div className="gla-budget-recommendation__recommendation">
				<GridiconInfoOutline />
				<div>
					{ createInterpolateElement(
						__(
							'Most merchants targeting <countryname /> set a daily budget of <budgetrange /> for approximately 10 conversions a week.',
							'google-listings-and-ads'
						),
						{
							countryname: <strong>{ countryName }</strong>,
							budgetrange: (
								<strong>
									{ createInterpolateElement(
										__(
											'<low /> to <high /> <currency />',
											'google-listings-and-ads'
										),
										{
											low: <>{ dailyBudgetLow }</>,
											high: <>{ dailyBudgetHigh }</>,
											currency: <>{ currency }</>,
										}
									) }
								</strong>
							),
						}
					) }
				</div>
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
