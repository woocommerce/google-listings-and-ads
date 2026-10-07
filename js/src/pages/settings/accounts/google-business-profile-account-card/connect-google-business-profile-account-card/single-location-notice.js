/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Flex, FlexItem } from '@wordpress/components';

/**
 * Internal dependencies
 */
import NoticeDetail from '../notice-detail';
import formatLocationAddress from '../format-location-address';
import CreateLink from './create-link';

/**
 * @typedef {import('~/data/types.js').GoogleBusinessProfileLocation} GoogleBusinessProfileLocation
 */

/**
 * Renders the notice shown when exactly one Google Business Profile location was found, with its
 * address and a "Create new location" link. The merchant confirms the location with the card's
 * "Connect" button. Once the merchant has clicked through to Google, the notice asks them to
 * refresh the page instead.
 *
 * @param {Object} props Component props.
 * @param {GoogleBusinessProfileLocation} props.location The location found.
 * @return {JSX.Element} The notice.
 */
export default function SingleLocationNotice( { location } ) {
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
							{ hasClickedCreate
								? __(
										'Refresh the page to see your new location.',
										'google-listings-and-ads'
								  )
								: __(
										'We found a Google Business Profile location.',
										'google-listings-and-ads'
								  ) }
						</p>
						<p>{ formatLocationAddress( location ) }</p>
					</>
				}
			/>
			<FlexItem>
				<CreateLink type="location" onClick={ handleCreateClick } />
			</FlexItem>
		</Flex>
	);
}
