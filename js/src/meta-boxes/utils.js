/**
 * Check if there are any recent paid campaigns.
 *
 * @param {Array} campaigns List of campaigns.
 * @return {boolean} True if there are recent paid campaigns, false otherwise.
 */
export const hasRecentPaidCampaigns = ( campaigns ) => {
	const fourteenDaysAgo = new Date();
	fourteenDaysAgo.setDate( fourteenDaysAgo.getDate() - 14 );

	return campaigns.some( ( campaign ) => {
		const campaignDate = new Date( campaign.start_date );

		return (
			campaign.status === 'enabled' &&
			campaign.type === 'performance_max' &&
			campaignDate >= fourteenDaysAgo
		);
	} );
};
