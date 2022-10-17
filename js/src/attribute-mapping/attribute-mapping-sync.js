/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Flex } from '@wordpress/components';

/**
 * Internal dependencies
 */
import SyncIcon from '.~/components/sync-icon';
import SuccessIcon from '.~/components/success-icon';
import usePolling from '.~/hooks/usePolling';
import { API_NAMESPACE } from '.~/data/constants';

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

	return (
		<Flex justify="start" align="center">
			{ data.is_syncing ? (
				<SyncIcon size={ 14 } />
			) : (
				<SuccessIcon size={ 14 } />
			) }
			<p className="gla-attribute-mapping__syncing">
				{ data.is_syncing
					? __(
							'Your products are being synced with your rules',
							'google-listings-and-ads'
					  )
					: __(
							'Your products are synced with your rules',
							'google-listings-and-ads'
					  ) }
			</p>
		</Flex>
	);
};

export default AttributeMappingSync;
