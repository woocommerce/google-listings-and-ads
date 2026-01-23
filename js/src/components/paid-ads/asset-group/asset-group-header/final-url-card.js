/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import classnames from 'classnames';
import { useCallback, useEffect, useRef, useState } from '@wordpress/element';
import { ExternalLink } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { ASSET_GROUP_KEY } from '~/constants';
import Section from '~/components/section';
import AccountCard, { APPEARANCE } from '~/components/account-card';
import AppButton from '~/components/app-button';
import AssetsLoader from './assets-loader';
import { API_NAMESPACE } from '~/data/constants';
import './final-url-card.scss';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';

/**
 * @typedef {import('~/data/types.js').SuggestedAssets} SuggestedAssets
 */

/**
 * Clicking on the "Or, select another page" button.
 *
 * @event gla_reselect_another_final_url_button_click
 */

function fetchSuggestedAssets( id, type ) {
	const endPoint = `${ API_NAMESPACE }/assets/suggestions`;
	const query = { id, type };
	return apiFetch( { path: addQueryArgs( endPoint, query ) } );
}

/**
 * Renders the Card UI for managing the final URL and getting the suggested assets.
 *
 * @param {Object} props React props.
 * @param {(suggestedAssets: SuggestedAssets | null) => void} props.onAssetsChange Callback function when the suggested assets are changed or reset to `null`.
 * @param {string} [props.initialFinalUrl] The initial final URL.
 * @param {boolean} [props.hideFooter=false] Whether to hide the card footer.
 *
 * @fires gla_reselect_another_final_url_button_click
 */
export default function FinalUrlCard( {
	onAssetsChange,
	initialFinalUrl,
	hideFooter = false,
} ) {
	const [ fetching, setFetching ] = useState( false );
	const [ finalUrl, setFinalUrl ] = useState( initialFinalUrl || null );
	const didInitialLoadRef = useRef( false );
	const { createNotice } = useDispatchCoreNotices();

	const description = finalUrl ? (
		<ExternalLink href={ finalUrl }>{ finalUrl }</ExternalLink>
	) : (
		__(
			'Choose a page that you want people to reach after clicking your ad. This might be your homepage, or a more specific page.',
			'google-listings-and-ads'
		)
	);

	const handleAssetsLoaded = useCallback(
		( suggestedAssets ) => {
			setFinalUrl( suggestedAssets[ ASSET_GROUP_KEY.FINAL_URL ] );
			onAssetsChange( suggestedAssets );
		},
		[ onAssetsChange ]
	);

	const handleReselectClick = () => {
		setFinalUrl( null );
		onAssetsChange( null );
	};

	const className = classnames( {
		'gla-final-url-card': true,
		'gla-final-url-card--has-selected-url': finalUrl,
	} );

	const loadSuggestedAssets = useCallback(
		async ( { id, type } ) => {
			setFetching( true );
			try {
				const assets = await fetchSuggestedAssets( id, type );
				handleAssetsLoaded( assets );
			} catch ( error ) {
				createNotice(
					'error',
					__(
						'Unable to load assets data.',
						'google-listings-and-ads'
					)
				);
			} finally {
				setFetching( false );
			}
		},
		[ createNotice, handleAssetsLoaded ]
	);

	useEffect( () => {
		if ( didInitialLoadRef.current ) {
			return;
		}

		didInitialLoadRef.current = true;
		loadSuggestedAssets( { id: 0, type: 'homepage' } );
	}, [ loadSuggestedAssets ] );

	return (
		<AccountCard
			className={ className }
			appearance={ APPEARANCE.FINAL_URL }
			alignIcon="top"
			description={ description }
		>
			<Section.Card.Footer align="end" gap={ 4 } hidden={ hideFooter }>
				{ finalUrl ? (
					<AppButton
						isTertiary
						text={ __(
							'Or, select a different Final URL',
							'google-listings-and-ads'
						) }
						eventName="gla_reselect_another_final_url_button_click"
						onClick={ handleReselectClick }
					/>
				) : (
					<AssetsLoader
						isFetching={ fetching }
						loadSuggestedAssets={ loadSuggestedAssets }
					/>
				) }
			</Section.Card.Footer>
		</AccountCard>
	);
}
