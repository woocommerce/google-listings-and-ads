/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useViewportMatch } from '@wordpress/compose';
import { useCallback, useEffect, useRef } from '@wordpress/element';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import TreeSelectControl from '~/components/tree-select-control';
import useMCCountryTreeOptions from './useMCCountryTreeOptions';
import './supported-country-select.scss';

/**
 * @typedef {import('~/data/actions').CountryCode} CountryCode
 */

/**
 * Returns a TreeSelectControl component with list of countries grouped by continents.
 *
 * This component is for selecting countries under Google Merchant Center supported countries.
 * And the selectable countries can be further limited by the `countryCodes` prop.
 *
 * All other props (except `countryCodes` prop) are forwarded to the underlying TreeSelectControl component.
 *
 * @param {Object} props React props.
 * @param {Array<CountryCode>} [props.countryCodes] Country codes for converting to tree-based option structure. It will use all MC supported countries if not specified.
 * @param {string} [props.className] Class name. Note that when there is a Form's `{...getInputProps('fieldname')}` being passed into this component, the className might be null.
 * @param {(visible: boolean) => void} [props.onDropdownVisibilityChange] Callback when the visibility of the dropdown options is changed.
 */
export default function SupportedCountrySelect( {
	countryCodes,
	className,
	onDropdownVisibilityChange,
	...restProps
} ) {
	const maxVisibleTags = useViewportMatch( 'medium', '<' ) ? 5 : 10;
	const treeOptions = useMCCountryTreeOptions( countryCodes );
	const containerRef = useRef();
	// Cache the found modal elements so cleanup can reference them after unmount.
	const modalElementsRef = useRef( {} );

	const handleDropdownVisibilityChange = useCallback(
		( visible ) => {
			if ( ! modalElementsRef.current.content ) {
				const content = containerRef.current?.closest(
					'.components-modal__content'
				);
				const modal = content?.parentElement;
				modalElementsRef.current = {
					content: content ?? null,
					modal: modal?.classList.contains( 'app-modal' )
						? modal
						: null,
				};
			}

			const { content, modal } = modalElementsRef.current;
			if ( content ) {
				content.style.overflow = visible ? 'visible' : '';
			}
			if ( modal ) {
				modal.style.overflow = visible ? 'visible' : '';
			}

			onDropdownVisibilityChange?.( visible );
		},
		[ onDropdownVisibilityChange ]
	);

	useEffect( () => {
		return () => {
			const { content, modal } = modalElementsRef.current;
			if ( content ) {
				content.style.overflow = '';
			}
			if ( modal ) {
				modal.style.overflow = '';
			}
		};
	}, [] );

	return (
		<div ref={ containerRef }>
			<TreeSelectControl
				className={ classnames(
					'gla-supported-country-select',
					className
				) }
				placeholder={ __(
					'Start typing to filter countries…',
					'google-listings-and-ads'
				) }
				selectAllLabel={ __(
					'All countries',
					'google-listings-and-ads'
				) }
				maxVisibleTags={ maxVisibleTags }
				options={ treeOptions }
				onDropdownVisibilityChange={ handleDropdownVisibilityChange }
				{ ...restProps }
			/>
		</div>
	);
}
