/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Flex, FlexBlock } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { API_NAMESPACE } from '~/data/constants';
import { useAppDispatch } from '~/data';
import AppButton from '~/components/app-button';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import SearchConsoleSelectControl from '../search-console-select-control';
import NoticeDetail from '../notice-detail';
import Connecting from './connecting';

/**
 * Clicking on the button to select an existing Search Console property.
 *
 * @event gla_search_console_property_select_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-search-console'.
 */

/**
 * Clicking on the button to create a new Search Console property.
 *
 * @event gla_search_console_property_create_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-search-console'.
 */

/**
 * Renders the property-selection step's detail: a selector lets the merchant choose which
 * candidate property to connect, alongside an explicit "Or, create a new..." action —
 * following the same pattern already used for Merchant Center and Google Ads account
 * selection, rather than folding "create new" into the select as an option.
 *
 * A single match or no match resolves automatically on the backend with zero merchant action,
 * so the selector itself is only ever needed once there are genuinely multiple candidates.
 * While the backend hasn't yet reported 2+ candidates — including while it's still silently
 * resolving a single-match or no-match property — this shows a neutral "setting up" treatment
 * instead.
 *
 * @fires gla_search_console_property_select_button_click
 * @fires gla_search_console_property_create_button_click
 *
 * @param {Object} props Component props.
 * @param {import('~/data/types.js').SearchConsoleAccount} props.account The Search Console account — always resolved by the time this renders, since `Detail` only renders it after `hasFinishedResolution`.
 * @return {JSX.Element} The detail.
 */
export default function PropertySelection( { account } ) {
	const { createNotice } = useDispatchCoreNotices();
	const { invalidateResolution } = useAppDispatch();
	const [ value, setValue ] = useState();

	const [ fetchSelectProperty, { loading } ] = useApiFetchCallback( {
		path: `${ API_NAMESPACE }/search-console/property`,
		method: 'POST',
	} );

	// Shared by both actions below: selecting an existing property and creating a new one both
	// POST to the same endpoint, differing only in the request payload.
	const submitProperty = async ( data ) => {
		try {
			await fetchSelectProperty( { data } );
			invalidateResolution( 'getSearchConsoleAccount', [] );
		} catch ( error ) {
			createNotice(
				'error',
				__(
					'Unable to select your Search Console property. Please try again later.',
					'google-listings-and-ads'
				)
			);
		}
	};

	const handleSelectClick = () => submitProperty( { url: value } );
	const handleCreateNewClick = () => submitProperty( { create_new: true } );

	if ( ( account.properties?.length ?? 0 ) < 2 ) {
		return <Connecting />;
	}

	return (
		<NoticeDetail
			status="info"
			title={ __(
				'We found multiple Google Search Console properties',
				'google-listings-and-ads'
			) }
			body={ __( 'Pick one to connect.', 'google-listings-and-ads' ) }
			extraContent={
				<Flex gap={ 3 } align="flex-end" expanded={ false } wrap>
					<FlexBlock>
						<SearchConsoleSelectControl
							value={ value }
							onChange={ setValue }
						/>
					</FlexBlock>
					<AppButton
						eventName="gla_search_console_property_select_button_click"
						eventProps={ { context: 'settings-search-console' } }
						onClick={ handleSelectClick }
						disabled={ ! value || loading }
						loading={ loading }
						isSecondary
					>
						{ __( 'Continue', 'google-listings-and-ads' ) }
					</AppButton>
				</Flex>
			}
			actions={ [
				<AppButton
					key="create-new"
					eventName="gla_search_console_property_create_button_click"
					eventProps={ { context: 'settings-search-console' } }
					onClick={ handleCreateNewClick }
					disabled={ loading }
					loading={ loading }
					isTertiary
				>
					{ __(
						'Or, create a new Search Console property',
						'google-listings-and-ads'
					) }
				</AppButton>,
			] }
		/>
	);
}
