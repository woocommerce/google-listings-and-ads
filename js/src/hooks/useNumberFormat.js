/**
 * Internal dependencies
 */
import useCurrencyFormat from './useCurrencyFormat';
import useStoreNumberSettings from './useStoreNumberSettings';

/**
 * Gets a formatter function to format a number based on the store's number (currency) settings.
 *
 * The formatted string does not contain currency code or symbol.
 *
 * By default, the number settings is set to `precision: 0`. You can override it with `overrideSettings`.
 *
 * @param {Object} [overrideSettings] Currency setting to override store's.
 */
const useNumberFormat = ( overrideSettings ) => {
	const numberSettings = useStoreNumberSettings( overrideSettings );

	return useCurrencyFormat( numberSettings );
};

export default useNumberFormat;
