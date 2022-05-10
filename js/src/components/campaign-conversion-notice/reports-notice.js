/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement, useState } from '@wordpress/element';
import { Notice, __experimentalText as Text } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { glaData, LOCAL_STORAGE_KEYS } from '.~/constants';
import localStorage from '.~/utils/localStorage';
import getConversionCampaignStatusNotice from '.~/utils/getConversionCampaignStatusNotice';
import AppDocumentationLink from '.~/components/app-documentation-link';

/**
 * Shows Notice {@link Notice}
 * providing information about the conversion status of PMax campaigns
 *
 * @fires gla_upgrade_campaign_learn_more_link_click with `{ context: 'reports-programs, linkId: 'campaign-conversion-status-after-migration-reports-read-more', href: '#' }`.
 *
 * @param {Object} props React props.
 * @param {string} props.context Context or page on which the notice is shown, to be forwarded to the link's track event.
 * @return {JSX.Element} {@link Notice} element with the info message and the link to the documentation.
 */
const CampaignConversionReportsNotice = ( { context } ) => {
	const conversionStatus = getConversionCampaignStatusNotice(
		glaData.adsCampaignConvertStatus
	);

	const defaultDismissedValue = localStorage.get(
		LOCAL_STORAGE_KEYS.IS_REPORTS_MIGRATION_NOTICE_DISMISSED
	)
		? true
		: false;

	const [ isDismissed, setIsDismissed ] = useState( defaultDismissedValue );

	const onRemove = () => {
		localStorage.set(
			LOCAL_STORAGE_KEYS.IS_REPORTS_MIGRATION_NOTICE_DISMISSED,
			true
		);
		setIsDismissed( true );
	};

	if ( isDismissed || conversionStatus !== 'AFTER_CONVERSION' ) {
		return null;
	}

	return (
		<Notice
			className="gla-campaign-conversion-status-reports-notice"
			onRemove={ onRemove }
		>
			<Text data-testid="gla-campaign-conversion-reports-notice">
				{ createInterpolateElement(
					__(
						'Your existing campaigns have been upgraded to Performance Max. <readMoreLink>Learn more about this upgrade</readMoreLink>',
						'google-listings-and-ads'
					),
					{
						readMoreLink: (
							<AppDocumentationLink
								context={ context }
								//TODO Update link when it is defined
								href="#"
								linkId="campaign-conversion-status-after-migration-reports-read-more"
							/>
						),
					}
				) }
			</Text>
		</Notice>
	);
};

export default CampaignConversionReportsNotice;
