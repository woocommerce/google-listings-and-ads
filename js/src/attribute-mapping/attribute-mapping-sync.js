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
		? formatDate(
				glaDateTimeFormat,
				new Date( data.last_sync * 1000 ).toString()
		  )
		: __( 'Never', 'google-listings-and-ads' );

	return (
		<Flex justify="start" align="center">
			<span>{ __( 'Last sync:', 'google-listings-and-ads' ) }</span>
			<span>{ data.is_scheduled ? scheduled : lastSync }</span>
		</Flex>
	);
};

export default AttributeMappingSync;
