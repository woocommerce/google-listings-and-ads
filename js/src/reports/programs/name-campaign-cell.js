/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppTooltip from '.~/components/app-tooltip';
import { glaData } from '.~/constants';
import isCampaignConverted from '.~/utils/isCampaignConverted';

/**
 * Shows Tooltip {@link Notice}
 * providing information about the conversion status of PMax campaigns
 *
 *
 * @param {Object} props React props.
 * @param {string} props.name Campaign Name
 * @param {string} props.type Campaign type
 * @return {JSX.Element} {@link Notice} element with the info message and the link to the documentation.
 */
const NameCampaignCell = ( { type, name } ) => {
	if ( isCampaignConverted( glaData.adsCampaignConvertStatus, type ) ) {
		return (
			<AppTooltip
				data-testid="tooltip-conversion-campaign"
				position="top right"
				text={ __(
					'This campaign has been upgraded to Performance Max',
					'google-listings-and-ads'
				) }
			>
				{ name }
			</AppTooltip>
		);
	}

	return name;
};

export default NameCampaignCell;
