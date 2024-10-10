/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import useMCProductStatistics from '.~/hooks/useMCProductStatistics';

const CreateCampaignNotice = () => {
	const { hasFinishedResolution, data } = useMCProductStatistics();

	if ( hasFinishedResolution && ! data ) {
		return __(
			'An error occurred while retrieving your product feed. Please try again later.',
			'google-listings-and-ads'
		);
	}
	const isLoading = ! hasFinishedResolution || data?.loading;

	if ( isLoading ) {
		return null;
	}
};

export default CreateCampaignNotice;
