/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Flex, FlexBlock } from '@wordpress/components';
import { info } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { API_NAMESPACE } from '~/data/constants';
import { useAppDispatch } from '~/data';
import AppButton from '~/components/app-button';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import useSearchConsoleAccount from '~/hooks/useSearchConsoleAccount';
import SearchConsoleSelectControl from './search-console-select-control';
import SearchConsoleNoticeAccountCard from './notice-account-card';
import ConnectingSearchConsoleAccountCard from './connecting-search-console-account-card';

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
 * Renders the property-selection step: while the backend is still resolving a single-match or
 * no-match property, the "connecting" card is shown; once multiple candidate properties are
 * reported, a selector lets the merchant choose which one to connect, alongside an explicit
 * "Or, create a new..." action — following the same pattern already used for Merchant Center
 * and Google Ads account selection, rather than folding "create new" into the select as an
 * option.
 *
 * @fires gla_search_console_property_select_button_click
 * @fires gla_search_console_property_create_button_click
 *
 * @return {JSX.Element} The account card.
 */
export default function PropertySelectionSearchConsoleAccountCard() {
	const { createNotice } = useDispatchCoreNotices();
	const { invalidateResolution } = useAppDispatch();
	const { account, hasFinishedResolution } = useSearchConsoleAccount();
	const properties = account?.properties ?? [];
	const [ value, setValue ] = useState();

	const [ fetchSelectProperty, { loading } ] = useApiFetchCallback( {
		path: `${ API_NAMESPACE }/search-console/property`,
		method: 'POST',
	} );

	const selectProperty = async ( data ) => {
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

	const handleSelectClick = () => selectProperty( { url: value } );
	const handleCreateNewClick = () => selectProperty( { create_new: true } );

	// Single-match or no-match: the backend has already resolved the property silently —
	// no prompt is shown.
	if ( ! hasFinishedResolution || properties.length <= 1 ) {
		return <ConnectingSearchConsoleAccountCard />;
	}

	// Multi-match: show the selector, with non-covering properties greyed out and
	// explained, plus an explicit "create new" action alongside it.
	return (
		<SearchConsoleNoticeAccountCard
			description={ __(
				'See how your store performs in Google Search.',
				'google-listings-and-ads'
			) }
			status="info"
			icon={ info }
			badgeLabel={ __( 'In progress', 'google-listings-and-ads' ) }
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
			action={
				<AppButton
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
				</AppButton>
			}
		/>
	);
}
