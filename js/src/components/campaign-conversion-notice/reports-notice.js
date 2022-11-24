/**
 * Internal dependencies
 */
import { glaData } from '.~/constants';
import CONVERSION_STATUSES from './conversion-statuses';
import getConversionCampaignStatusNotice from '.~/utils/getConversionCampaignStatusNotice';
import DismissibleNotice from '.~/components/dismissible-notice';
import Text from '.~/components/app-text';
import './reports-notice.scss';

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
		<DismissibleNotice
			className="gla-campaign-conversion-status-reports-notice"
			localStorageKey={ status.localStorageKey }
		>
			<Text data-testid="gla-campaign-conversion-reports-notice">
				{ status.content }
			</Text>
		</DismissibleNotice>
	);
};

export default CampaignConversionReportsNotice;
