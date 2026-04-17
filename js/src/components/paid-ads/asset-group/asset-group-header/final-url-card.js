/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { useCallback, useEffect, useState } from '@wordpress/element';
import { ExternalLink } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { ASSET_GROUP_KEY } from '~/constants';
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import Section from '~/components/section';
import AccountCard, { APPEARANCE } from '~/components/account-card';
import AppButton from '~/components/app-button';
import AssetsLoader from './assets-loader';
import './final-url-card.scss';

/**
 * @typedef {import('~/data/types.js').SuggestedAssets} SuggestedAssets
 */

/**
 * Clicking on the "Or, select another page" button.
 *
 * @event gla_reselect_another_final_url_button_click
 */

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
	const { adapter } = useAdaptiveFormContext();
	const [ finalUrl, setFinalUrl ] = useState( null );
	const { fetchAssets } = adapter;

	const description = finalUrl ? (
		<ExternalLink href={ finalUrl }>{ finalUrl }</ExternalLink>
	) : (
		__(
			'Choose a page that you want people to reach after clicking your ad. This might be your homepage, or a more specific page.',
			'google-listings-and-ads'
		)
	);

	useEffect( () => {
		setFinalUrl( initialFinalUrl );
	}, [ initialFinalUrl ] );

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

	const fetchCampaignAssets = useCallback(
		async ( id, type ) => {
			const suggestedAssets = await fetchAssets( id, type );
			handleAssetsLoaded( suggestedAssets );
		},
		[ fetchAssets, handleAssetsLoaded ]
	);

	const className = classnames( {
		'gla-final-url-card': true,
		'gla-final-url-card--has-selected-url': finalUrl,
	} );

	const handleSelectFinalUrl = async ( selectedFinalUrl ) => {
		const { id, type } = selectedFinalUrl;

		await fetchCampaignAssets( id, type );
	};

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
					<AssetsLoader onSelectFinalUrl={ handleSelectFinalUrl } />
				) }
			</Section.Card.Footer>
		</AccountCard>
	);
}
