/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Flex, FlexBlock, FlexItem } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import LoadingLabel from '~/components/loading-label';
import useGoogleSearchConsoleProperties from '~/hooks/useGoogleSearchConsoleProperties';
import useResolveSearchConsoleProperty from '~/hooks/useResolveSearchConsoleProperty';
import GoogleSearchConsoleSelectControl from '../google-search-console-select-control';
import NoticeDetail from '../notice-detail';
import './property-selection.scss';

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
 * Renders the property-selection step's detail: a notice explaining the multi-match, a selector
 * to choose which candidate property to connect, and a confirm action alongside an explicit
 * create-new action.
 *
 * A single match or no match auto-resolves on the frontend with zero merchant action (see
 * {@see useAutoResolveSearchConsoleProperty}, mounted above this in the card tree), so the
 * selector itself only ever renders when there is a genuine, unresolved multi-match returned by
 * `GET search-console/properties`. That list is read from the data store, so the resolver's own
 * fetch-once-and-cache behavior covers both the initial load and the loading state below, with
 * no manual fetch/effect code needed here.
 *
 * @fires gla_google_search_console_property_select_button_click
 * @fires gla_google_search_console_property_create_button_click
 *
 * @return {JSX.Element|null} The detail, or `null` when there is nothing to show.
 */
export default function PropertySelection() {
	const { properties, hasFinishedResolution } =
		useGoogleSearchConsoleProperties();
	const [ value, setValue ] = useState();
	const [ pendingAction, setPendingAction ] = useState( null );
	const [ resolveProperty, { loading } ] = useResolveSearchConsoleProperty();

	// `resolveProperty` already surfaces an error notice and refreshes the store on failure
	// (see `useResolveSearchConsoleProperty`) — the `try`/`catch` here only exists to swallow
	// the re-thrown rejection so it doesn't surface as an unhandled promise rejection.
	const handleSelectClick = async () => {
		setPendingAction( 'select' );
		try {
			await resolveProperty( value );
		} catch ( error ) {
			// Already handled above.
		} finally {
			setPendingAction( null );
		}
	};

	const handleCreateNewClick = async () => {
		setPendingAction( 'create' );
		try {
			await resolveProperty();
		} catch ( error ) {
			// Already handled above.
		} finally {
			setPendingAction( null );
		}
	};

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

	// A single match or no match is handled entirely by the auto-resolve hook — this only
	// ever has something to show for a genuine multi-match.
	if ( ! properties || properties.length < 2 ) {
		return null;
	}

	return (
		<Flex direction="column" gap={ 4 }>
			<FlexBlock>
				<NoticeDetail
					status="info"
					body={
						<>
							<p className="gla-google-search-console-account-card__property-selection-notice">
								{ __(
									'We found multiple Google Search Console properties.',
									'google-listings-and-ads'
								) }
							</p>
							<p className="gla-google-search-console-account-card__property-selection-notice">
								{ __(
									'Pick one to connect, or create a new one.',
									'google-listings-and-ads'
								) }
							</p>
						</>
					}
				/>
				<GoogleSearchConsoleSelectControl
					properties={ properties }
					value={ value }
					onChange={ setValue }
				/>
			</FlexBlock>
			<FlexItem>
				<Flex justify="flex-start" gap={ 4 }>
					<AppButton
						eventName="gla_google_search_console_property_select_button_click"
						eventProps={ {
							context: 'settings-search-console',
						} }
						onClick={ handleSelectClick }
						disabled={ ! value }
						loading={ loading && pendingAction === 'select' }
						isPrimary
					>
						{ __( 'Save', 'google-listings-and-ads' ) }
					</AppButton>
					<AppButton
						eventName="gla_google_search_console_property_create_button_click"
						eventProps={ { context: 'settings-search-console' } }
						onClick={ handleCreateNewClick }
						loading={ loading && pendingAction === 'create' }
						isTertiary
					>
						{ __(
							'Create new property',
							'google-listings-and-ads'
						) }
					</AppButton>
				</Flex>
			</FlexItem>
		</Flex>
	);
}
