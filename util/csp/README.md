# CSP tooling

Support for running USLIMS under a strict Content-Security-Policy.

See ehb54/ultrascan-tickets#478.

## csp-audit.sh

Scans a checkout for constructs that `default-src 'self'` blocks and exits
non-zero if it finds any, so it can gate a merge.

```bash
util/csp/csp-audit.sh /path/to/us3lims_dbinst /path/to/us3lims_common
```

It checks the sources rather than rendered pages. That is deliberate: most of
this markup is emitted from PHP on paths that only fire for particular user
levels, particular analysis types, or particular error conditions, and browser
testing reliably misses them. It reports:

| check | directive |
|---|---|
| inline `on*=` event handler attributes | `script-src` |
| inline `<script>` blocks | `script-src` |
| `javascript:` URLs | `script-src` |
| `eval` / `new Function` / string timers | `script-src 'unsafe-eval'` |
| inline `style=` attributes | `style-src` |
| inline `<style>` blocks | `style-src` |
| cross-origin `src=` / `data=` subresources | `default-src` |
| cross-origin CSS `@import` / `url()` | `style-src`, `font-src`, `img-src` |
| PHP tags inside `.js` files | — see below |

Vendored libraries (`jquery*.js`) are skipped: they are loaded as external
files, so their internals are not a CSP concern.

`blob:` URL construction is reported separately and does **not** count as a
violation — it is an input to the policy, not a defect. See below.

### Why the PHP-in-`.js` check is here

A `<?php ... ?>` tag inside a file served as `.js` never executes. The raw tag
reaches the browser, the script fails to parse, and *every* function it defines
becomes undefined. When those functions are the targets of delegated CSP
handlers, the symptom is indistinguishable from a botched CSP conversion, so
the check belongs next to the CSP checks. `js/GA_2.js` had exactly this
problem.

## Deploying the policy

`csp-report-only.conf` is the recommended starting point. Deploy it in
report-only mode first, collect for a full analysis cycle, then switch the
header name to `Content-Security-Policy`.

The policy is not simply `default-src 'self'`. Three additions are load-bearing:

- **`blob:`** in `img-src`, `object-src` and `connect-src`. The supporting-files
  viewer builds object URLs with `URL.createObjectURL()` and feeds them to
  `<object data=>`, `<img src=>` and `fetch()`. `blob:` is not covered by
  `'self'`, so without these the document viewer silently shows nothing.
- **`form-action`, `base-uri`, `frame-ancestors`** must be stated explicitly.
  None of them fall back to `default-src`, so a policy that omits them leaves
  form submission targets, `<base>` injection and framing unrestricted.

Note that `style-src 'self'` still permits JavaScript to set element styles
through the CSSOM (`element.style.display = ...`, jQuery `.show()`, jQuery UI
sliders). CSP only blocks style *attributes parsed from markup*. No code had to
change for that.
