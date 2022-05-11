/**
 * External dependencies
 */
import { __experimentalText as Text } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { glaData } from '.~/constants';
import CONVERSION_STATUSES from './conversion-statuses';
import getConversionCampaignStatusNotice from '.~/utils/getConversionCampaignStatusNotice';
import MigrationCampaignNotice from './migration-notice';

/**
 * Shows Notice {@link Notice}
 * providing information about the conversion status of PMax campaigns
 *
 *
 * @return {JSX.Element} {@link Notice} element with the info message and the link to the documentation.
 */
const CampaignConversionReportsNotice = () => {
	const conversionStatus = getConversionCampaignStatusNotice(
		glaData.adsCampaignConvertStatus
	);

	const status = CONVERSION_STATUSES.REPORTS_CONVERSION;

	if ( conversionStatus !== 'AFTER_CONVERSION' ) {
		return null;
	}

	return (
		<MigrationCampaignNotice
			className="gla-campaign-conversion-status-reports-notice"
			localStorageKey={ status.localStorageKey }
		>
			<Text data-testid="gla-campaign-conversion-reports-notice">
				{ status.content }
			</Text>
		</MigrationCampaignNotice>
	);
};

export default CampaignConversionReportsNotice;
