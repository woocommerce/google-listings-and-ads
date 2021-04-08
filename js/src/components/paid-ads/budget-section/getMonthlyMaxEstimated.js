/**
 * Get the monthly max estimated cost based on the daily average cost.
 *
 * The monthly max estimated cost is essentially daily average cost * 30.4 (the average number of days per month).
 *
 * This is based on the following Google Ads Help Center articles:
 *
 * * [Choose your bid and budget](https://support.google.com/google-ads/answer/2375454)
 * * [About campaign budgets](https://support.google.com/google-ads/answer/6385083)
 *
 * @param {number} dailyAverageCost The daily average cost provided by user.
 */
const getMonthlyMaxEstimated = ( dailyAverageCost ) => {
	return dailyAverageCost * 30.4;
};

export default getMonthlyMaxEstimated;
