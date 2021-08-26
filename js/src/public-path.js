/**
 * This is needed in Webpack < 5, as we cannot use eiither
 * `import.meta` or `publicPath: 'auto'`,
 * but we do need Webpack's `publicPath` for `file-loader` to create accurate URLs.
 */
const url = new URL( document.currentScript.src ).href;
// eslint-disable-next-line camelcase, no-undef
__webpack_public_path__ = url.slice( 0, url.lastIndexOf( '/' ) + 1 );
