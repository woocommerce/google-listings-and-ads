/**
 * Internal dependencies
 */
import useStoreCurrency from '.~/hooks/useStoreCurrency';

/**
 * Get the store's number settings, which is based on the store's currency settings.
 *
 * By default, the number settings is set to `precision: 0`. You can override it with `overrideSettings`.
 *
 * @param {Object} [overrideSettings] Override settings.
 * @return {Object} The store's number settings object.
 */
const useStoreNumberSettings = ( overrideSettings ) => {
	const currencySetting = useStoreCurrency();

	return {
		...currencySetting,
		precision: 0,
		...overrideSettings,
	};
};

export default useStoreNumberSettings;
