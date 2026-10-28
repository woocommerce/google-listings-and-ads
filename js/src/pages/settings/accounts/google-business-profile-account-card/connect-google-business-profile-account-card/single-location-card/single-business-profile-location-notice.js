/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex, FlexItem } from '@wordpress/components';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import NoticeDetail from '../../notice-detail';
import formatLocationAddress from '../../utils/format-location-address';
import CreateNewLink from '../create-new-link';
import CreateNewRefreshNotice from '../create-new-refresh-notice';

/**
 * @typedef {import('~/data/types.js').GoogleBusinessProfileLocation} GoogleBusinessProfileLocation
 */

/**
 * Renders the notice shown when exactly one Google Business Profile location was found: its
 * address, plus a create-new-location link, with the refresh reminder below the notice once that
 * link is clicked. The merchant connects the location with the card's "Connect" button.
 *
 * @param {Object} props Component props.
 * @param {GoogleBusinessProfileLocation} props.location The single location found.
 * @return {JSX.Element} The notice.
 */
export default function SingleBusinessProfileLocationNotice( { location } ) {
	const [ hasClickedCreate, setHasClickedCreate ] = useState( false );

	const handleCreateClick = () => {
		setHasClickedCreate( true );
	};

	return (
		<Flex direction="column" gap={ 4 }>
			<NoticeDetail
				status="info"
				body={
					<>
						<p>
							{ __(
								'We found a Google Business Profile location.',
								'google-listings-and-ads'
							) }
						</p>
						<p>{ formatLocationAddress( location ) }</p>
					</>
				}
			/>
			{ hasClickedCreate && <CreateNewRefreshNotice type="location" /> }
			<FlexItem>
				<CreateNewLink type="location" onClick={ handleCreateClick } />
			</FlexItem>
		</Flex>
	);
}
