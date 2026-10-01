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
| cross-origin `src=` / `data=` subresources and `<link href=>` | `default-src`, `style-src` |
| cross-origin CSS `@import` / `url()` | `style-src`, `font-src`, `img-src` |
| forms posting to an absolute URL | `form-action` |
| PHP tags inside `.js` files | see below |

Matching is case-insensitive (`STYLE=`, `onClick=` and `<SCRIPT` are all
caught). In `.js` files, handlers and `style=` are reported only inside markup
strings such as `innerHTML = "<a onclick=...>"`; property assignments like
`element.onclick = fn` are allowed by CSP and are not reported.

Vendored libraries (`jquery*.js`) are skipped by this text scanner to avoid
noisy matches. External scripts can still perform operations CSP blocks (for
example jQuery evaluating scripts inside AJAX fragments). Browser tests must
verify their runtime behavior; a clean scan does not establish compatibility.

**CLEAN does not mean ready to enforce.** The scan only knows the patterns
above. HTML loaded from the database is invisible to it and needs runtime
review. The dbinst report viewer extracts stored UltraScan report bodies,
removes their inline `<style>` blocks, and serves the table-padding rule from
`css/reports.css`, so those reports no longer trigger `style-src` violations
under `style-src 'self'`. Run report-only, read the log, and run the browser
tests before switching to enforcement.

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

Violations are sent to `/csp-report.php` (in `us3lims_webinfo`, at the web
root), which writes each one to the PHP error log as a `CSP violation:` line.
With php-fpm on EL8 that is `/var/log/php-fpm/www-error.log`; under mod_php it
is the Apache error log. Deploy webinfo with the policy: without it the reports
get a 404 and nothing is collected, though nothing else breaks.

The policy is not simply `default-src 'self'`. These additions are load-bearing:

- **`blob:`** in `img-src`, `object-src`, `connect-src` and `frame-src`. The supporting-files
  viewer builds object URLs with `URL.createObjectURL()` and feeds them to
  `<object data=>`, `<img src=>` and `fetch()`. `blob:` is not covered by
  `'self'`, so without these the document viewer silently shows nothing. Chromium also creates a frame for its
  embedded PDF viewer, so `frame-src blob:` is required for that path.
- **`form-action`, `base-uri`, `frame-ancestors`** must be stated explicitly.
  None of them fall back to `default-src`, so a policy that omits them leaves
  form submission targets, `<base>` injection and framing unrestricted.
- **`report-uri /csp-report.php`**. Without it, violations only reach each
  visitor's browser console and the report-only phase collects nothing.

Note that `style-src 'self'` still permits JavaScript to set element styles
through the CSSOM (`element.style.display = ...`, jQuery `.show()`, jQuery UI
sliders). CSP only blocks style *attributes parsed from markup*. No code had to
change for that.
