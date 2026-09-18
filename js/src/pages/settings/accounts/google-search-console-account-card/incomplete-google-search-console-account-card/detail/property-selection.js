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

const DEFAULT_NOTICE = () => ( {
	status: 'info',
	body: (
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
	),
} );

/**
 * Renders the property-selection step's detail: a notice, a selector to choose which candidate
 * property to connect (when any are available), and a confirm action alongside an explicit
 * create-new action.
 *
 * Reused for two different situations, distinguished by the `notice` and `alwaysShowCreateAction`
 * props the caller passes: the initial multi-match setup step (default notice, nothing rendered
 * at all when there are no candidates — a single match or no match having already resolved
 * automatically on the backend with zero merchant action, so a genuinely empty list here would
 * mean nothing is pending), and the action-needed step for a property that's since been deleted
 * or lost verified ownership — see {@see ./action-needed-property-selection.js}, which always has
 * something to offer (at least creating a new property) even with zero other candidates. Either
 * way, submitting the same already-selected property re-triggers its verification, so this
 * doubles as the "just re-verify" action too.
 *
 * @param {Object} [props]
 * @param {(hasCandidates: boolean) => {status: 'info'|'warning', title?: string, body: string|JSX.Element}} [props.notice]
 *   Builds the notice content to show above the selector, given whether any candidates are
 *   available. Defaults to the initial multi-match copy.
 * @param {boolean} [props.alwaysShowCreateAction] When `true`, keeps the notice and "Create new
 *   property" action visible even with zero candidates, instead of rendering nothing.
 *
 * @fires gla_google_search_console_property_select_button_click
 * @fires gla_google_search_console_property_create_button_click
 *
 * @return {JSX.Element|null} The detail, or `null` while still loading or while there is nothing to show.
 */
export default function PropertySelection( {
	notice = DEFAULT_NOTICE,
	alwaysShowCreateAction = false,
} = {} ) {
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

	if ( ! hasCandidates && ! alwaysShowCreateAction ) {
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
				<NoticeDetail { ...notice( hasCandidates ) } />
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
