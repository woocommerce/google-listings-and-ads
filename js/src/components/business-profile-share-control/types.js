/**
 * The outcome of a post's last send to Google Business Profile, as `SyncOutcome` defines it on the
 * server.
 *
 * @typedef {'queued'|'success'|'sent-pending-verification'|'sent-location-restricted'|'request-failure'|'content-rejection'|'account-restriction'|'account-disconnected'|'not-found'} SyncOutcomeValue
 */

/**
 * A post's last send to Google Business Profile, shaped like the post's `gla_gbp_sync_outcome`
 * REST field.
 *
 * @typedef {Object} SyncOutcome
 * @property {SyncOutcomeValue} outcome The outcome of the send.
 * @property {string} message The message Google returned, if any.
 * @property {boolean} image_omitted Whether the post was sent without its image.
 * @property {string|null} display_message The message to show the merchant, or `null` when there's nothing to tell them.
 * @property {'error'|'warning'|null} display_status The message's severity, or `null` without a message.
 */

/**
 * Whether a post has been shared to Google Business Profile, shaped like the post's
 * `gla_gbp_share_status` REST field.
 *
 * @typedef {Object} ShareStatus
 * @property {boolean} is_shared Whether the post has been shared to Google.
 * @property {string} search_url The post's link on Google, once Google provides one.
 */

// This export is required for JSDoc in other files to import the type definitions from this file.
export default {};
