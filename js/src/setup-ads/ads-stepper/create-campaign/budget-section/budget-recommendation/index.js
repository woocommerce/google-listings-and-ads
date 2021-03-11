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
import './index.scss';

const BudgetRecommendation = ( props ) => {
	const { recommendation, showLowerBudgetNotice } = props;
	const {
		country_code: countryCode,
		daily_budget_low: dailyBudgetLow,
		daily_budget_high: dailyBudgetHigh,
		currency,
	} = recommendation;
	const map = useCountryKeyNameMap();
	const countryName = map[ countryCode ];

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
