/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { useState, useRef } from '@wordpress/element';
import { Spinner } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import AppButton from '.~/components/app-button';
import SelectControl from '.~/wcdl/select-control';
import { API_NAMESPACE } from '.~/data/constants';
import './assets-loader.scss';

/**
 * @typedef {import('.~/data/types.js').SuggestedAssets} SuggestedAssets
 */

function allowAllResults() {
	// Make it result in `new RegExp('.', 'i')` to avoid any custom results in
	// the mapFinalUrlsToOptions function being filtered out.
	return '.';
}

function fetchFinalUrls( search ) {
	const endPoint = `${ API_NAMESPACE }/assets/final-url/suggestions`;
	const query = { search };
	return apiFetch( { path: addQueryArgs( endPoint, query ) } );
}

function fetchSuggestedAssets( id, type ) {
	const endPoint = `${ API_NAMESPACE }/assets/suggestions`;
	const query = { id, type };
	return apiFetch( { path: addQueryArgs( endPoint, query ) } );
}

function mapFinalUrlsToOptions( finalUrls, search ) {
	const options = finalUrls.map( ( finalUrl ) => ( {
		finalUrl,
		key: `${ finalUrl.type }-${ finalUrl.id }`,
		keywords: [ finalUrl.title ],
		label: (
			<>
				<div className="gla-assets-loader__option-title">
					{ finalUrl.title }
				</div>
				<div className="gla-assets-loader__option-url">
					{ finalUrl.url }
				</div>
			</>
		),
	} ) );

	// Querying with empty `search` means getting the suggestion pages by default.
	// Prepend a label to indicate that the results are suggestions.
	if ( search === '' && finalUrls.length ) {
		options.unshift( {
			key: 'disabled-option-suggestion',
			label: __( 'SUGGESTIONS', 'google-listings-and-ads' ),
			isDisabled: true,
		} );
	}

	if ( finalUrls.length === 0 ) {
		options.unshift( {
			key: 'disabled-option-no-results',
			label: __( 'No matching results', 'google-listings-and-ads' ),
			keywords: [ search ],
			isDisabled: true,
		} );
	}

	return options;
}

/**
 * Clicking on the "Scan for assets" button.
 *
 * @event gla_import_assets_by_final_url_button_click
 * @property {string} type The type of the selected Final URL suggestion to be imported. Possible values: `post`, `term`, `homepage`.
 */

/**
 * Renders the UI for querying pages, selecting a wanted page as the final URL,
 * and then loading the suggested assets.
 *
 * @param {Object} props React props.
 * @param {(suggestedAssets: SuggestedAssets) => void} props.onAssetsLoaded Callback function when the suggested assets are loaded.
 *
 * @fires gla_import_assets_by_final_url_button_click
 */
export default function AssetsLoader( { onAssetsLoaded } ) {
	const cacheRef = useRef( {} );
	const latestSearchRef = useRef();

	// The selector allows only one option to be selected which is expected, and the array is used
	// here to get the entire data of the selected option rather than getting its key only.
	// Ref: https://github.com/woocommerce/woocommerce/blob/6.9.0/packages/js/components/src/select-control/index.js#L137-L141
	const [ selectedOptions, setSelectedOptions ] = useState( [] );
	const [ searching, setSearching ] = useState( false );
	const [ fetching, setFetching ] = useState( false );
	const { createNotice } = useDispatchCoreNotices();

	// To have the searching state and keep the entered search value, this handler needs to
	// be called immediately after keying values. Therefore, it also needs to implement the
	// debounce here.
	const debouncedHandleSearch = async ( prevOptions, rawSearch ) => {
		if ( ! searching ) {
			setSearching( true );
		}

		// Workaround to keep the entered search value.
		if ( rawSearch !== selectedOptions[ 0 ]?.label ) {
			setSelectedOptions( [ { label: rawSearch } ] );
		}

		// Workaround to debounce the calls.
		const delay = new Promise( ( resolve ) => setTimeout( resolve, 300 ) );
		latestSearchRef.current = delay;

		await delay;

		// Ensure only the latest call will be passed down.
		if ( latestSearchRef.current !== delay ) {
			return prevOptions;
		}

		const cache = cacheRef.current;
		const search = rawSearch.trim().toLowerCase();

		cache[ search ] ??= fetchFinalUrls( search ).then( ( finalUrls ) =>
			mapFinalUrlsToOptions( finalUrls, search )
		);

		cache[ search ].finally( () => {
			setSearching( false );
		} );

		return cache[ search ];
	};

	const handleChange = ( [ option ] ) => {
		if ( option ) {
			const selectedOption = { ...option, label: option.finalUrl.title };
			setSelectedOptions( [ selectedOption ] );
		} else {
			setSelectedOptions( [] );
		}
	};

	const handleClick = async () => {
		const { finalUrl } = selectedOptions[ 0 ];

		setFetching( true );

		fetchSuggestedAssets( finalUrl.id, finalUrl.type )
			.then( onAssetsLoaded )
			.catch( () => {
				setFetching( false );
				createNotice(
					'error',
					__(
						'Unable to load assets data from the selected page.',
						'google-listings-and-ads'
					)
				);
			} );
	};

	const { finalUrl } = selectedOptions[ 0 ] || {};

	return (
		<>
			<SelectControl
				className="gla-assets-loader"
				label={
					<>
						{ __( 'Select final URL', 'google-listings-and-ads' ) }
						{ searching && <Spinner /> }
					</>
				}
				placeholder={ __( 'Search page', 'google-listings-and-ads' ) }
				isSearchable
				hideBeforeSearch
				excludeSelectedOptions={ false }
				disabled={ fetching }
				options={ [] } // The actual options will be provided via the callback results of `onSearch`.
				selected={ selectedOptions }
				onSearch={ debouncedHandleSearch }
				onChange={ handleChange }
				getSearchExpression={ allowAllResults }
			/>
			<AppButton
				isSecondary
				text={
					fetching ? '' : __( 'Select', 'google-listings-and-ads' )
				}
				eventName="gla_import_assets_by_final_url_button_click"
				eventProps={ { type: finalUrl?.type } }
				disabled={ ! finalUrl }
				loading={ fetching }
				onClick={ handleClick }
			/>
		</>
	);
}
