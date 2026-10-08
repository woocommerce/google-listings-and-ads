/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex, FlexItem } from '@wordpress/components';

/**
 * Internal dependencies
 */
import NoticeDetail from '../../notice-detail';
import formatLocationAddress from '../../format-location-address';
import CreateNewLocationLink from './create-new-location-link';

/**
 * @typedef {import('~/data/types.js').GoogleBusinessProfileLocation} GoogleBusinessProfileLocation
 */

/**
 * Renders the notice shown when exactly one Google Business Profile location was found: its
 * address, plus a create-new-location link. The merchant connects it with the card's "Connect"
 * button.
 *
 * @param {Object} props Component props.
 * @param {GoogleBusinessProfileLocation} props.location The single location found.
 * @param {JSX.Element|null} props.createLocationNotice The refresh reminder shown above the create-new-location link once it's been clicked.
 * @param {() => void} props.onCreateLocationClick Callback when the user clicks the create-new-location link.
 * @return {JSX.Element} The notice.
 */
export default function SingleBusinessProfileLocationNotice( {
	location,
	createLocationNotice,
	onCreateLocationClick,
} ) {
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
			{ createLocationNotice }
			<FlexItem>
				<CreateNewLocationLink onClick={ onCreateLocationClick } />
			</FlexItem>
		</Flex>
	);
}
