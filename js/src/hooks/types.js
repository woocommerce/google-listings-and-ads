/**
 * @typedef {Object} ImageMedia
 * @property {number} id The media ID of the image.
 * @property {string} url URL.
 * @property {number} width Width.
 * @property {number} height Height.
 * @property {number} filesizeInBytes File size in bytes.
 * @property {string} alt Alternate text.
 */

/**
 * @typedef {Object} StoreAddress
 * @property {string} address Store address line 1.
 * @property {string} address2 Address line 2.
 * @property {string} city Store city.
 * @property {string} state Store country state if available.
 * @property {string} country Store country.
 * @property {string} postcode Store postcode.
 * @property {boolean|null} isAddressFilled Whether the minimum address data is filled in.
 *                          `null` if data have not loaded yet.
 * @property {boolean|null} isMCAddressDifferent Whether the address data from WC store and GMC are the same.
 *                          `null` if data have not loaded yet.
 * @property {string[]} missingRequiredFields The missing required fields of the store address.
 */

// This export is required for JSDoc in other files to import the type definitions from this file.
export default {};
