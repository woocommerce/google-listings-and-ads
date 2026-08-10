/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { API_NAMESPACE } from '~/data/constants';
import { useAppDispatch } from '~/data';
import AccountCard, { APPEARANCE } from '~/components/account-card';
import AppButton from '~/components/app-button';
import LoadingLabel from '~/components/loading-label';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import useExistingSearchConsoleProperties from '~/hooks/useExistingSearchConsoleProperties';
import SearchConsoleSelectControl, {
	CREATE_NEW_PROPERTY_VALUE,
} from './search-console-select-control';

/**
 * Clicking on the button to select (or create) a Search Console property.
 *
 * @event gla_search_console_property_select_button_click
 * @property {string} context Indicates from which page the button was clicked. Possible value: 'settings-search-console'.
 */

/**
 * Renders the property-selection step of the Search Console connect flow.
 *
 * When the backend has already auto-resolved a single usable property, or found no usable
 * property and silently created one, no selector is shown at all — the card
 * simply reflects that resolution is in progress. Only when the backend reports multiple
 * candidate properties is the actual selector (with a "Create new" option) rendered.
 *
 * @fires gla_search_console_property_select_button_click
 */
const PropertySelector = () => {
	const { createNotice } = useDispatchCoreNotices();
	const { invalidateResolution } = useAppDispatch();
	const { data: properties, hasFinishedResolution } =
		useExistingSearchConsoleProperties();
	const [ value, setValue ] = useState();

	const [ fetchSelectProperty, { loading } ] = useApiFetchCallback( {
		path: `${ API_NAMESPACE }/search-console/property`,
		method: 'POST',
	} );

	const handleSelectClick = async () => {
		try {
			const isCreatingNew = value === CREATE_NEW_PROPERTY_VALUE;

			await fetchSelectProperty( {
				data: isCreatingNew ? { create_new: true } : { url: value },
			} );
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

	// Single-match or no-match: the backend has already resolved the property silently —
	// no prompt is shown.
	if ( ! hasFinishedResolution || ( properties ?? [] ).length <= 1 ) {
		return (
			<AccountCard
				appearance={ APPEARANCE.GOOGLE_SEARCH_CONSOLE }
				description={ __(
					'Setting up your Search Console property…',
					'google-listings-and-ads'
				) }
				indicator={
					<LoadingLabel
						text={ __( 'Setting up…', 'google-listings-and-ads' ) }
					/>
				}
			/>
		);
	}

	// Multi-match: show the selector, with non-covering properties greyed out and
	// explained, plus the "Create new" option.
	return (
		<AccountCard
			appearance={ APPEARANCE.GOOGLE_SEARCH_CONSOLE }
			description={ __(
				'We found more than one Search Console property for your store. Choose the one to connect.',
				'google-listings-and-ads'
			) }
			detail={
				<SearchConsoleSelectControl
					value={ value }
					onChange={ setValue }
				/>
			}
			indicator={
				<AppButton
					isSecondary
					disabled={ ! value }
					loading={ loading }
					eventName="gla_search_console_property_select_button_click"
					eventProps={ { context: 'settings-search-console' } }
					onClick={ handleSelectClick }
				>
					{ __( 'Continue', 'google-listings-and-ads' ) }
				</AppButton>
			}
		/>
	);
};

export default PropertySelector;
