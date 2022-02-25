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

const BudgetRecommendation = ( props ) => {
	const { countryCodes, dailyAverageCost } = props;
	const [ countryCode ] = countryCodes;
	const { data } = useFetchBudgetRecommendationEffect( countryCodes );
	const map = useCountryKeyNameMap();

	if ( ! data ) {
		return null;
	}

	const { currency, recommendations } = data;
	const [ recommendation ] = recommendations;
	const {
		daily_budget_low: dailyBudgetLow,
		daily_budget_high: dailyBudgetHigh,
	} = recommendation;
	const countryName = map[ countryCode ];

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
