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
import { GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS } from '~/constants';
import { useAppDispatch } from '~/data';
import AppButton from '~/components/app-button';
import LoadingLabel from '~/components/loading-label';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import useGoogleSearchConsoleAccount from '~/hooks/useGoogleSearchConsoleAccount';
import useGoogleSearchConsoleProperties from '~/hooks/useGoogleSearchConsoleProperties';
import GoogleSearchConsoleSelectControl from '../google-search-console-select-control';
import NoticeDetail from '../notice-detail';
import { SEARCH_CONSOLE_EVENT_CONTEXT } from '../../constants';
import './property-selection.scss';

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
 * Renders the property-selection step's detail: a notice, a selector to choose which candidate
 * property to connect (when any are available), and a confirm action alongside an explicit
 * create-new action. Shows the action-needed notice (the previously connected property is no
 * longer usable) instead of the initial multi-match notice, and keeps the "Create new property"
 * action visible even with zero candidates, when the account's status is `action-needed`.
 *
 * @fires gla_google_search_console_property_select_button_click
 * @fires gla_google_search_console_property_create_button_click
 *
 * @return {JSX.Element|null} The detail, or `null` while still loading or while there is nothing to show.
 */
export default function PropertySelection() {
	const { account } = useGoogleSearchConsoleAccount();
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
	} );

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

	const hasCandidates = properties?.length > 0;
	const actionNeeded =
		account?.status === GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS.ACTION_NEEDED;

	if ( ! hasCandidates && ! actionNeeded ) {
		return null;
	}

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

	const handleSelectClick = () => {
		submitProperty( selectProperty );
	};

	const handleCreateNewClick = () => {
		submitProperty( createProperty );
	};

	return (
		<Flex direction="column" gap={ 4 }>
			<FlexBlock>
				{ actionNeeded && (
					<NoticeDetail
						status="warning"
						title={ __(
							'Your Search Console property needs attention',
							'google-listings-and-ads'
						) }
						body={
							hasCandidates
								? __(
										'There is an issue with the connected property. It may have been deleted, or the connected account may no longer have verified access to it. Select another property below, or create a new one.',
										'google-listings-and-ads'
								  )
								: __(
										'There is an issue with the connected property. It may have been deleted, or the connected account may no longer have verified access to it. Create a new property to reconnect.',
										'google-listings-and-ads'
								  )
						}
					/>
				) }

				{ ! actionNeeded && (
					<NoticeDetail
						status="info"
						body={
							<div className="gla-google-search-console-account-card__property-selection-notice">
								<p>
									{ __(
										'We found multiple Google Search Console properties.',
										'google-listings-and-ads'
									) }
								</p>
								<p>
									{ __(
										'Pick one to connect, or create a new one.',
										'google-listings-and-ads'
									) }
								</p>
							</div>
						}
					/>
				) }

				{ hasCandidates && (
					<GoogleSearchConsoleSelectControl
						label={ __(
							'Select a property',
							'google-listings-and-ads'
						) }
						properties={ properties }
						value={ value }
						onChange={ setValue }
					/>
				) }
			</FlexBlock>
			<FlexItem>
				<Flex justify="flex-start" gap={ 4 }>
					{ hasCandidates && (
						<AppButton
							eventName="gla_google_search_console_property_select_button_click"
							eventProps={ {
								context: SEARCH_CONSOLE_EVENT_CONTEXT,
							} }
							onClick={ handleSelectClick }
							disabled={ ! value }
							loading={ isSelecting }
							isPrimary
						>
							{ __( 'Save', 'google-listings-and-ads' ) }
						</AppButton>
					) }
					<AppButton
						eventName="gla_google_search_console_property_create_button_click"
						eventProps={ { context: SEARCH_CONSOLE_EVENT_CONTEXT } }
						onClick={ handleCreateNewClick }
						loading={ isCreating }
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
