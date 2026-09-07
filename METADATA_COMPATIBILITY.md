# Research metadata — numeric CSV format 2.4 — 2026-09-06

## Agreed implementation

One `uslims_job_metadata.php`, the existing utility/AUC dependencies, and the two
existing formatter filenames. No additional collector files, structured exports,
text columns, percent-encoding, nested bundles or extensive provenance machinery.
The established whitespace-delimited CSV-style transport and trailing record
identifier are retained. Formatter definitions list numeric inputs, targets and
text-to-number category mappings. Format 2.4 supersedes the experimental 2.1–2.3
layouts; none of those changes was deployed in this session.

The max10 formatter emits **199 named columns** (196 inputs/recordkeeping values
and three targets), followed by the original-style record identifier with a result
suffix. The full formatter supports 250 dataset slots. Inputs are naturally
sorted, followed by naturally sorted targets. The collector writes values in
exactly the generated header order. Historical numeric-only formatters remain
readable; frozen historical exports are untouched.

## Research information retained

| Purpose | Numeric fields |
|---|---|
| Separate algorithms and variants | `@attributes.method`: 1 standard 2DSA, 2 DMGA, 3 GA, 4 PCSA, 5 2DSA-CG; `analysis_variant` maps exact analysis labels to separate numeric codes in both formatters |
| 2DSA work | Original bounds/grid/repetition/iteration/MC/noise controls, boundary-fit selector, global and dataset counts |
| CG and DMGA work | CG component/subgrid counts; DMGA floating constraints/base components/base associations; model IDs and coded lookup status; DMGA p_grid/minimize controls |
| GA/PCSA work | GA bucket count and original fixed-bucket, population, generation and evolution controls; original PCSA curve, axis, variation, fitting and regularization controls |
| Dataset geometry | Per-dataset scans, points, simulation points, radial/time grids, meniscus and bottom |
| Speed-dependent work | Maximum rotor speed, maximum single-step duration and speed-step count per dataset—the quantities used by the current source-grounded work model |
| Resources/outcomes | Parsed requested nodes/ranks/explicit cores/memory/wall limit; existing CPUCount/CPUTime/wallTime/max_rss and actual master groups |
| Later selection/completeness | Submission/start/end/update timestamps; request/result/experiment IDs; format version; coded classification/model/resource/result status; request-XML validity |

This preserves the operands for the current scientific-work representation,
raw-information parity and runtime-extrapolation questions. Derived work
signatures can be calculated afterward from the same inputs used by baselines.
It is not a lossless dump of raw XML, model contents, bucket geometry or ordered
speed profiles, nor a promise that every future research question is answerable.
A question requiring those complete artifacts, historical build/load data or
phase timings would require a separate justified acquisition decision.

Model contents and related input rows may be missing historically; counts are
not inferred from filenames. Requested resources remain missing when unavailable
or unsupported. There is no invented executable/build history, load or timing
measurement. A successful extraction does not establish manuscript readiness.

## Missing values and categories

**NA means missing.** It is recognized by pandas as a missing numeric value;
it is not a text predictor or a new encoding. Real negative numeric values are
retained. Unused dataset slots remain zero; actual counts distinguish padding.
Historical formatter files retain the older -1 convention.

`method_classification_status`: 0 classified, 1 missing CG reference,
2 missing DC reference, 3 invalid request XML, 4 missing label,
5 unrecognized label, 6 conflicting method labels, 7 conflicting model reference.
Ambiguous families are NA. Model status: 0 available, 1 missing, 2 invalid.
Resource status, scheduler and queue-status mappings are listed in the formatters.
Unknown categories are NA and are discoverable using the existing
`--list-string-variants` option. The trailing DB/request/result identifier allows
source records to be located without adding encoded names to the numeric table.

Standard 2DSA and CG are classified before formatting, using request/analysis
labels and model-reference evidence. Variant codes retain MC/global/fit/curve
labels without redundant CG/DC presence flags. Status fields distinguish missing
or contradictory evidence from an ordinary family assignment.

`non_predictor_fields` explicitly lists numeric IDs, dates, observed resources
and quality/status fields. They remain in collected/extracted data for selection
but are excluded by the updated model-preprocessing configuration and generated
Python example. Numeric encoding alone does not make a field a valid predictor.

## Collection and slicing

No date cutoff. Explicit CLI database, analysis and dataset filters still apply.
`--limit` bounds requests examined per database, including incomplete requests.
Failed, missing and multiple results are retained as separate identifiable rows.
The current Ansible command selects known dataset counts 1–10; widen
`max_datasets` and select the full formatter to include larger global jobs.
The collector refuses to silently truncate a request exceeding formatter capacity.

The header-aware preprocessor separates all five families, retains global and
incomplete rows, and keeps unresolved and historical mixed-2DSA records separate.
The raw/extracted CSV is the basis for completeness-by-year/method checks before
choosing a window, eligible rows, predictors or an experiment.

For a small live test after deployment to the chosen server:

```bash
ansible-playbook metadata-playbook.yml --limit demeler4 --list-hosts
ansible-playbook metadata-playbook.yml --limit demeler4 -e limit_results=20 -e metadata_dir=/home/usadmin/metadata-test
```

That is one host and up to 20 requests per database. `git pull` updates the
server's existing branch; it does not transfer these uncommitted local changes.
Use the same collector/formatter version across hosts before merging CSV files.

## Verification

Collector tests live in the PEARC27 repository under `method/tests/dbutils/`;
they are not part of the dbutils deployment. Run these from the PEARC27 root.

```bash
python3 method/tests/dbutils/test_legacy_metadata_compat.py
.venv/bin/python method/tests/dbutils/test_enriched_metadata_csv.py
.venv/bin/python preprocess/tests/test_csv_column_order.py
php -l dbutils/uslims_job_metadata.php
```

Offline tests exercise the actual single-file collector with database/AUC
fixtures: numeric-only cells, old mappings, all five families and variants,
conflicts, missing inputs/models/results, multiple results, geometry and resource
values, wide requests, header alignment and exclusion of recordkeeping columns
from prediction inputs. No live collection or deployment was performed.
