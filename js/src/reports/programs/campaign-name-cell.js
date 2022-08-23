/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppTooltip from '.~/components/app-tooltip';
import './campaign-name-cell.scss';

/**
 * Shows Tooltip {@link Notice}
 * providing information about the conversion status of PMax campaigns
 *
 *
 * @param {Object} props React props.
 * @param {string} props.name Campaign Name
 * @param {boolean} props.isConverted Weather the campaign is converted
 * @return {JSX.Element} element with the migration tooltip or the campaign name
 */
const CampaignNameCell = ( { isConverted, name } ) => {
	if ( isConverted ) {
		return (
			<div className="gla-reports__tooltip-campaign-name">
				<AppTooltip
					position="top right"
					text={ __(
						'This campaign has been upgraded to Performance Max',
						'google-listings-and-ads'
					) }
				>
					{ name }
				</AppTooltip>
			</div>
		);
	}

	return name;
};

export default CampaignNameCell;
