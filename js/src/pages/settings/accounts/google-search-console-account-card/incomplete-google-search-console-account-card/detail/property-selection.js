/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Flex, FlexBlock, FlexItem } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { API_NAMESPACE } from '~/data/constants';
import { useAppDispatch } from '~/data';
import AppButton from '~/components/app-button';
import LoadingLabel from '~/components/loading-label';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import useGoogleSearchConsoleProperties from '~/hooks/useGoogleSearchConsoleProperties';
import GoogleSearchConsoleSelectControl from '../google-search-console-select-control';
import NoticeDetail from '../notice-detail';

const PROPERTIES_PATH = `${ API_NAMESPACE }/search-console/properties`;

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
 * candidate property to connect, alongside an explicit "Or, create a new..." action.
 *
 * A single match or no match resolves automatically on the backend with zero merchant action,
 * so the selector itself only ever renders when there is a genuine, unresolved multi-match
 * returned by `GET search-console/properties`. That list is read from the data store, so the
 * resolver's own fetch-once-and-cache behavior covers both the initial load and the loading
 * state below, with no manual fetch/effect code needed here.
 *
 * @fires gla_google_search_console_property_select_button_click
 * @fires gla_google_search_console_property_create_button_click
 *
 * @return {JSX.Element|null} The detail, or `null` when there is nothing to show.
 */
export default function PropertySelection() {
	const { properties, hasFinishedResolution } =
		useGoogleSearchConsoleProperties();
	const { createNotice } = useDispatchCoreNotices();
	const { invalidateResolution } = useAppDispatch();
	const [ value, setValue ] = useState();

	const [ selectProperty, { loading: isSelecting } ] = useApiFetchCallback( {
		path: PROPERTIES_PATH,
		method: 'POST',
		data: { site_url: value },
	} );

	const [ createProperty, { loading: isCreating } ] = useApiFetchCallback( {
		path: PROPERTIES_PATH,
		method: 'POST',
		data: {},
	} );

	const loading = isSelecting || isCreating;

	// Shared by both actions below: `fetchProperty` is whichever already-configured request
	// (`selectProperty` or `createProperty`) the caller wants to submit — both need identical
	// success/failure handling, differing only in which endpoint call they wrap.
	const submitProperty = async ( fetchProperty ) => {
		try {
			await fetchProperty();
			invalidateResolution( 'getGoogleSearchConsoleAccount', [] );
		} catch ( error ) {
			// Nothing changed server-side on failure (e.g. the chosen match is no longer
			// usable) — refresh to get a fresh property list and show the selector again.
			invalidateResolution( 'getGoogleSearchConsoleAccount', [] );
			invalidateResolution( 'getGoogleSearchConsoleProperties', [] );
			createNotice(
				'error',
				__(
					'The selected property is no longer available. Please try again.',
					'google-listings-and-ads'
				)
			);
		}
	};

	const handleSelectClick = () => submitProperty( selectProperty );
	const handleCreateNewClick = () => submitProperty( createProperty );

	if ( ! hasFinishedResolution ) {
		return (
			<LoadingLabel
				text={ __(
					'Loading Google Search Console properties…',
					'google-listings-and-ads'
				) }
			/>
		);
	}

	if ( ! properties?.length ) {
		return null;
	}

	return (
		<NoticeDetail
			status="info"
			title={ __(
				'We found multiple Google Search Console properties',
				'google-listings-and-ads'
			) }
			body={
				<Flex direction="column" gap={ 3 }>
					{ __(
						'Pick one to connect, or create a new one.',
						'google-listings-and-ads'
					) }
					<Flex direction="column" gap={ 3 } expanded={ false }>
						<FlexBlock>
							<GoogleSearchConsoleSelectControl
								properties={ properties }
								value={ value }
								onChange={ setValue }
							/>
						</FlexBlock>
						<FlexItem>
							<AppButton
								eventName="gla_google_search_console_property_select_button_click"
								eventProps={ {
									context: 'settings-search-console',
								} }
								onClick={ handleSelectClick }
								disabled={ ! value || loading }
								loading={ isSelecting }
								isSecondary
							>
								{ __( 'Save', 'google-listings-and-ads' ) }
							</AppButton>
						</FlexItem>
					</Flex>
				</Flex>
			}
			actions={ [
				<AppButton
					key="create-new"
					eventName="gla_google_search_console_property_create_button_click"
					eventProps={ { context: 'settings-search-console' } }
					onClick={ handleCreateNewClick }
					disabled={ loading }
					loading={ isCreating }
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
