/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import useEventPropertiesFilter from '.~/hooks/useEventPropertiesFilter';

export default async function expectEventWithPropertiesFilter(
	Component,
	filterName,
	queryElement,
	eventName,
	extraPropertiesGroups
) {
	function HookedComponent( { extraProperties } ) {
		useEventPropertiesFilter( filterName, {
			...extraProperties,
			__testInvalidPropertyNameShouldBeIgnored: '123',
		} );
		return <Component />;
	}

	const { rerender } = render();

	expect( recordEvent ).toHaveBeenCalledTimes( 0 );

	const testPropertiesGroups = [
		null, // Test component without extra event properties
		...extraPropertiesGroups,
		null, // Test extra event properties cleaned from global `@wordpress/hooks`
	];

	for ( let i = 0; i < testPropertiesGroups.length; i += 1 ) {
		const extraProperties = testPropertiesGroups[ i ];

		if ( extraProperties ) {
			rerender( <HookedComponent extraProperties={ extraProperties } /> );
		} else {
			rerender( <Component /> );
		}

		await userEvent.click( await queryElement() );

		const times = i + 1;

		expect( recordEvent ).toHaveBeenCalledTimes( times );
		expect( recordEvent ).toHaveBeenNthCalledWith(
			times,
			eventName,
			extraProperties || {}
		);
	}
}
