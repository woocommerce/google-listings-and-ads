/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import useEventPropertiesFilter from '.~/hooks/useEventPropertiesFilter';

/**
 * Expects a component to record an event with filtered event properties after performing the incoming action.
 *
 * The component will be tested:
 * 1. without the `useEventPropertiesFilter` hook to assert there are no filtered properties.
 * 2. with the `useEventPropertiesFilter` hook to assert the filtered properties are added.
 * 3. without the `useEventPropertiesFilter` hook again to assert the filtered properties are not left after the hook is unmounted.
 *
 * @param {JSX.Element} Component Component to be tested.
 * @param {string} filterName Filter name to be used in the `useEventPropertiesFilter` hook.
 * @param {Function} performAction Callback function to perform the action that triggers the event tracking. This function will be called multiple times to test different scenarios.
 * @param {string} eventName Event name expected to be recorded.
 * @param {Array<Object>} extraPropertiesGroups An array of test cases for the extra properties to be tested. Each extra properties case will be passed to the `useEventPropertiesFilter` hook and expected it be logged with the respective event.
 */
export default async function expectComponentToRecordEventWithFilteredProperties(
	Component,
	filterName,
	performAction,
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

		await performAction();

		const times = i + 1;

		expect( recordEvent ).toHaveBeenCalledTimes( times );
		expect( recordEvent ).toHaveBeenNthCalledWith(
			times,
			eventName,
			extraProperties
				? expect.objectContaining( extraProperties )
				: expect.anything()
		);
		expect( recordEvent ).not.toHaveBeenCalledWith(
			eventName,
			expect.objectContaining( {
				__testInvalidPropertyNameShouldBeIgnored: expect.anything(),
			} )
		);
	}
}
