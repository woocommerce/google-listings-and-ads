/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Flex } from '@wordpress/components';
import { format as formatDate } from '@wordpress/date';

/**
 * Internal dependencies
 */
import usePolling from '.~/hooks/usePolling';
import { API_NAMESPACE } from '.~/data/constants';
import glaDateTimeFormat from '.~/utils/date';
import HelpPopover from '.~/components/help-popover';

const AttributeMappingSync = () => {
	const { data, start } = usePolling( {
		path: `${ API_NAMESPACE }/mc/mapping/sync`,
	} );

	useEffect( () => {
		start();
	}, [ start ] );

	if ( ! data ) {
		return null;
	}

	const scheduled = __( 'Scheduled for sync', 'google-listings-and-ads' );
	const lastSync = data.last_sync
		? formatDate( glaDateTimeFormat, new Date( data.last_sync * 1000 ) )
		: __( 'Never', 'google-listings-and-ads' );

	return (
		<Flex justify="end" align="center">
			<strong>{ __( 'Last sync:', 'google-listings-and-ads' ) }</strong>
			<span className="gla-attribute-mapping__sync-status">
				{ data.is_scheduled ? scheduled : lastSync }
			</span>
			<HelpPopover
				id={ `gla-attribute-mapping-last-sync-helper-popover` }
				position="bottom left"
			>
				<div>
					{ __(
						'When an attribute rule is added or changed, data will be synced to Google Merchant Center via an async job. Note that it may take a while for the update to show up on Merchant Center, especially if it involves products that have not been synced and approved before.',
						'google-listings-and-ads'
					) }
				</div>
			</HelpPopover>
		</Flex>
	);
};

export default AttributeMappingSync;
