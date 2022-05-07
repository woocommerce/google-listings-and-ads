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

const NameCampaignCell = ( row ) => {
	if ( isCampaignConverted( glaData.adsCampaignConvertStatus, row.type ) ) {
		return (
			<AppTooltip
				data-testid="tooltip-conversion-campaign"
				position="top right"
				text={ __(
					'This campaign has been upgraded to Performance Max',
					'google-listings-and-ads'
				) }
			>
				{ row.name }
			</AppTooltip>
		);
	}

	return row.name;
};

export default NameCampaignCell;
