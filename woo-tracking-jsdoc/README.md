# woo-tracking-jsdoc

JSDoc template to report Tracking events to markdown file.

## Usage

```sh
jsdoc -r your/source/files/ -t ./woo-tracking-jsdoc -c .jsdocrc.json
```

Add your `/TRACKING.md` template

```md
# Usage Tracking

Some nice general description.

<woo-tracking-jsdoc></woo-tracking-jsdoc>
```

## Config

You may add any of the following properties to your JSDoc config (`.jsdocrc.json`) to change those default values:
```js
{
  "templates": {
    "woo-tracking-jsdoc": {
      // Path to the markdown file to which the tracking events' docs should be added
      "path": "TRACKING.md",
      // Pattern to be used to match the content to be replaced. The groups are respectively: start marker, replaceable content, end marker.
      "replacement": "(<woo-tracking-jsdoc(?:\\s[^>]*)?>)([\\s\\S]*)(<\\/woo-tracking-jsdoc.*>)"
    }
  }
```

## Imported types

If your codebase uses TS-style of importing types `{import('foo').bar}`, you will most probably get an error, like:
```
ERROR: Unable to parse a tag's type expression for source file … Invalid type expression "import('foo').bar"
```

To mitigate that use a `jsdoc-plugin-typescript` plugin to skip those. `npm install --save-dev jsdoc-plugin-typescript` and add this to your config:
```js
{
  "plugins": [
    "jsdoc-plugin-typescript"
  ],
  "typescript": {
    "moduleRoot": "assets/source" // Path to your module's root directory.
  }
```

## `~` Alias

If your codebase uses a `.~` or `~` alias for the root directory, you may use `tilde-alias`.

```js
{
  "plugins": [
    "woo-tracking-jsdoc/tilde-alias.js",
    "jsdoc-plugin-typescript"
  ],
  "typescript":
  // …
```