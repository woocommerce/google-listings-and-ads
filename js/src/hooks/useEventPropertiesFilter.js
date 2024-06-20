/**
 * External dependencies
 */
import { useEffect, useCallback, useReducer } from '@wordpress/element';
import { pick, uniqueId } from 'lodash';

/**
 * Internal dependencies
 */
import useIsEqualRefValue from './useIsEqualRefValue';
import { NAMESPACE, hooks, filterPropertiesMap } from '.~/utils/tracks';

/**
 * Hook to propagate event properties across multiple layers.
 *
 * For example, a button that is rendering a child layer requires state or context
 * held by the parent component when recording event tracking, and they span many
 * layers. Use this hook to avoid prop drilling.
 *
 * @param {string} filterName The name of the filter to use.
 * @param {Object} extraProperties The extra event properties to be added. Only property names listed in the respective `filterPropertiesMap` are processed.
 * @return {(properties: Object) => Object} The function to call to filter the event properties.
 */
export default function useEventPropertiesFilter(
	filterName,
	extraProperties
) {
	const validExtraProperties = useIsEqualRefValue(
		pick( extraProperties, filterPropertiesMap.get( filterName ) )
	);

	const [ , forceUpdate ] = useReducer( ( x ) => x + 1, 0 );

	useEffect( () => {
		const namespace = `${ NAMESPACE }/${ uniqueId() }`;

		if ( Object.keys( validExtraProperties ).length ) {
			hooks.addFilter( filterName, namespace, ( properties ) => {
				return {
					...properties,
					...validExtraProperties,
				};
			} );
		}

		forceUpdate();

		return () => {
			hooks.removeFilter( filterName, namespace );
		};
	}, [ filterName, validExtraProperties ] );

	return useCallback(
		( properties ) => hooks.applyFilters( filterName, properties ),
		[ filterName ]
	);
}
