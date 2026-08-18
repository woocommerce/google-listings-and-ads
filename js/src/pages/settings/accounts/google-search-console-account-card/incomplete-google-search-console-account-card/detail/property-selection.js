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
import useGoogleSearchConsoleAccount from '~/hooks/useGoogleSearchConsoleAccount';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import GoogleSearchConsoleSelectControl from '../google-search-console-select-control';
import NoticeDetail from '../notice-detail';
import Connecting from './connecting';

/**
 * Clicking on the button to select an existing Google Search Console property.
 *
 * @event gla_google_search_console_property_select_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-search-console'.
 */

/**
 * Clicking on the button to create a new Google Search Console property.
 *
 * @event gla_google_search_console_property_create_button_click
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
 * @fires gla_google_search_console_property_select_button_click
 * @fires gla_google_search_console_property_create_button_click
 *
 * @return {JSX.Element} The detail.
 */
export default function PropertySelection() {
	const { account } = useGoogleSearchConsoleAccount();
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
			invalidateResolution( 'getGoogleSearchConsoleAccount', [] );
		} catch ( error ) {
			createNotice(
				'error',
				__(
					'Unable to select your Google Search Console property. Please try again later.',
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
						<GoogleSearchConsoleSelectControl
							value={ value }
							onChange={ setValue }
						/>
					</FlexBlock>
					<AppButton
						eventName="gla_google_search_console_property_select_button_click"
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
					eventName="gla_google_search_console_property_create_button_click"
					eventProps={ { context: 'settings-search-console' } }
					onClick={ handleCreateNewClick }
					disabled={ loading }
					loading={ loading }
					isTertiary
				>
					{ __(
						'Or, create a new Google Search Console property',
						'google-listings-and-ads'
					) }
				</AppButton>,
			] }
		/>
	);
}
