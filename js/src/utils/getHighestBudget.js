/*
 * If a merchant selects more than one country, the budget recommendation
 * takes the highest country out from the selected countries.
 *
 * For example, a merchant selected Brunei (20 USD) and Croatia (15 USD),
 * then the budget recommendation should be (20 USD).
 */
export default function getHighestBudget( recommendations ) {
	if ( ! recommendations ) {
		return null;
	}

	return recommendations.reduce(
		( defender, challenger ) => {
			if ( challenger.daily_budget > defender.daily_budget ) {
				return challenger;
			}
			return defender;
		},
		{
			daily_budget: 0,
			country: '',
		}
	);
}
