/**
 * @typedef { import(".~/data/actions").CountryCode } CountryCode
 */

/**
 * @typedef {Object} ShippingRateGroup
 * @property {Array<CountryCode>} countries Array of selected country codes.
 * @property {string} method Shipping method, e.g. "flat_rate".
 * @property {string} currency Currency.
 * @property {number} rate Rate value.
 */

export default {};
