/**
 * External dependencies
 */
import {
	Notice,
	Icon,
	__experimentalText as Text,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { external as externalIcon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { glaData } from '.~/constants';
import localStorage from '.~/utils/localStorage';
import AppDocumentationLink from '.~/components/app-documentation-link';
import CONVERSION_STATUSES from './conversion-statuses';
import getConversionCampaignStatusNotice from '.~/utils/getConversionCampaignStatusNotice';
import './index.scss';

const ExternalIcon = () => (
	<Icon
		className="gla-campaign-conversion-status-notice__external_icon"
		icon={ externalIcon }
		size={ 18 }
	/>
);

/**
 * Shows Notice {@link Notice}
 * providing information about the conversion status of PMax campaigns
 *
 * @fires gla_upgrade_campaign_learn_more_link_click with `{ context: 'dashboard, linkId: 'campaign-conversion-status-before-migration-read-more | campaign-conversion-status-after-migration-read-more', href: '#' }`.
 * @fires gla_upgrade_campaign_reports_link_click with  `{ context: 'dashboard, href: '/google/reports' }` it is fire after the migration is completed.
 *
 * @param {Object} props React props.
 * @param {string} props.context Context or page on which the notice is shown, to be forwarded to the link's track event.
 * @return {JSX.Element} {@link Notice} element with the info message and the link to the documentation.
 */
const CampaignConversionStatusNotice = ( { context } ) => {
	const conversionStatus = getConversionCampaignStatusNotice(
		glaData.adsCampaignConvertStatus
	);
	const status = CONVERSION_STATUSES[ conversionStatus ];

	const defaultDismissedValue = localStorage.get( status?.localStorageKey )
		? true
		: false;

	const [ isDismissed, setIsDismissed ] = useState( defaultDismissedValue );

	const onRemove = () => {
		localStorage.set( status?.localStorageKey, true );
		setIsDismissed( true );
	};

	if ( isDismissed || ! status ) {
		return null;
	}

	return (
		<Notice
			className="gla-campaign-conversion-status-notice"
			onRemove={ onRemove }
		>
			<Text
				variant="subtitle.small"
				className="gla-campaign-conversion-status-notice__title"
				data-testid="gla-campaign-conversion-dashboard-notice"
			>
				{ status.title }
			</Text>
			<p>{ status.content }</p>
			<p className="gla-campaign-conversion-status-notice__external_link">
				<AppDocumentationLink
					context={ context }
					linkId={ status.externalLink.linkId }
					href={ status.externalLink.link }
				>
					{ status.externalLink.content }
					<ExternalIcon />
				</AppDocumentationLink>
			</p>
		</Notice>
	);
};

export default CampaignConversionStatusNotice;
