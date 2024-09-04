/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import { Tip } from '@wordpress/components';
import GridiconNoticeOutline from 'gridicons/dist/notice-outline';

/**
 * Internal dependencies
 */
import useCountryKeyNameMap from '.~/hooks/useCountryKeyNameMap';
import useFetchBudgetRecommendationEffect from './useFetchBudgetRecommendationEffect';
import useTargetAudienceFinalCountryCodes from '.~/hooks/useTargetAudienceFinalCountryCodes';
import './index.scss';

/*
 * If a merchant selects more than one country, the budget recommendation
 * takes the highest country out from the selected countries.
 *
 * For example, a merchant selected Brunei (20 USD) and Croatia (15 USD),
 * then the budget recommendation should be (20 USD).
 */
function getHighestBudget( recommendations ) {
	return recommendations.reduce( ( defender, challenger ) => {
		if ( challenger.daily_budget > defender.daily_budget ) {
			return challenger;
		}
		return defender;
	} );
}

function toRecommendationRange( isMultiple, ...values ) {
	const conversionMap = { strong: <strong />, em: <em />, br: <br /> };
	const template = isMultiple
		? // translators: it's a range of recommended budget amount. 1: the value of the budget, 2: the currency of amount.
		  __(
				'Google will optimize your ads to maximize performance across the country/s you select.<br /><em>Tip: Most merchants targeting similar countries <strong>set a daily budget of %1$f %2$s</strong></em>',
				'google-listings-and-ads'
		  )
		: // translators: it's a range of recommended budget amount. 1: the value of the budget, 2: the currency of amount 3: a country name selected by the merchant.
		  __(
				'Google will optimize your ads to maximize performance across the country/s you select.<br /><em>Tip: Most merchants targeting <strong>%3$s set a daily budget of %1$f %2$s</strong></em>',
				'google-listings-and-ads'
		  );

	return createInterpolateElement(
		sprintf( template, ...values ),
		conversionMap
	);
}

const BudgetRecommendation = ( props ) => {
	const { dailyAverageCost = Infinity } = props;
	const { data: countryCodes } = useTargetAudienceFinalCountryCodes();
	const { data } = useFetchBudgetRecommendationEffect( countryCodes );
	const map = useCountryKeyNameMap();

	if ( ! data ) {
		return null;
	}

	const { currency, recommendations } = data;
	const { daily_budget: dailyBudget, country } =
		getHighestBudget( recommendations );

	const countryName = map[ country ];
	const recommendationRange = toRecommendationRange(
		recommendations.length > 1,
		dailyBudget,
		currency,
		countryName
	);

	const showLowerBudgetNotice = dailyAverageCost < dailyBudget;

	return (
		<div className="gla-budget-recommendation">
			{ showLowerBudgetNotice && (
				<div className="gla-budget-recommendation__low-budget">
					<GridiconNoticeOutline />
					<div>
						{ __(
							'With a budget lower than your competitor range, your campaign may not get noticeable results.',
							'google-listings-and-ads'
						) }
					</div>
				</div>
			) }
			<Tip>{ recommendationRange }</Tip>
		</div>
	);
};

export default BudgetRecommendation;
