# us3lims autoflow/submitctl/submitone test harness

Tracking issue: ehb54/ultrascan-tickets#930

## Context

The original trigger for this work was a stuck CLI job submission
(`makeafrequest.php` → `autoflowAnalysis` row stuck at `status=READY`) on a
us3lims_gridctl deployment. Root cause turned out to be file-permission issues for
the `services.php`-managed daemons (`submitctl.php`), not a code bug.

That investigation exposed how easy it is for this pipeline to fail silently partway
through (daemon not running, stale lock, permission issue, a transient `sbatch`
failure) with no easy way to reproduce or watch it happening. The result: a **PHP CLI
test harness** that can drive the full autoflow pipeline end-to-end, watch every part
of it live, and (eventually) assert pass/fail for regression testing — including
deliberately injecting `sbatch` failures to exercise the failure paths (PR
ehb54/us3lims_gridctl#27's `check_cli_errors_in_dump()`, `submitctl.php`'s
FAILED/`scancel` handling, and `submit_local.php`'s retry/backoff in
`attemptSubmit()`).

## The pipeline being tested (for reference)

1. `us3lims_gridctl/autoflow_util/makeafrequest*.php <db> <invID> <rawID>` inserts
   `autoflow` / `analysisprofile` / `autoflowAnalysis` rows. `statusJson` starts as
   `{"to_process":[stage, stage, ...]}`. Several variants exist for different job
   shapes (`makeafrequest.php` = plain 2DSA chain, `makeafrequestPCSA*.php`,
   `makeafrequest2DSA_MC_cluster.php`, `makeafrequest2DSA-CG.php` — the latter is the
   most recently touched, Sep 2025).
2. `us3lims_gridctl/submitctl.php` — long-running daemon, polls every
   `$poll_sleep_seconds` (30s). State machine driven by `$failed_status`,
   `$completed_status`, `$wait_status` maps (submitctl.php:33-35) checked against
   `autoflowAnalysis.status`. On each tick (submitctl.php:280-370ish):
   - WAIT status + `nextWaitStatus` set → clears to `nextWaitStatus`
   - row marked `processing_key` ("submitted") and not yet completed/failed → skip (still in flight)
   - completed/failed → archive current stage into `statusJson.processed[]`
   - more `to_process` left and not failed → shift next stage into `submitted`, set
     `status='READY'`, **spawn** `php submitone.php $lims_db $ID >> submit.log 2>&1 &`
   - nothing left and completed → `status='FINISHED'`
   - failed → `scancel` the grid job if slurm
3. `us3lims_gridctl/submitone.php` — does the actual submission for one stage. Builds
   fake `$_SESSION`/`$_POST` from the `people` row (no browser session needed), then
   `include()`s `queue_setup_1.php`, `queue_setup_2.php` (which chains
   `queue_setup_3.php`) and the job-type page, capturing all output via
   `ob_start("dump_it")` into `$dumpfilebase/$lims_db-$ID.txt`
   (`/home/us3/lims/etc/submit/<db>-<ID>.txt`). `check_cli_errors()` /
   `check_cli_errors_in_dump()` (PR #27) scan that dump for `ERROR:` lines and call
   `fail_job()` → sets `autoflowAnalysis.status='FAILED'`.
4. The actual grid submission happens deep inside the included pages, eventually
   reaching `us3lims_common/class/submit_local.php:submit_job()` →
   `attemptSubmit()`, which runs `sbatch --get-user-env $workdir/us3.slurm` directly
   for local/slurm clusters with no `login` override, or over `ssh` for remote
   clusters (`submit_local.php:488-553`). Retries/backoff are controlled by
   `$global_sbatch_submit_retries` / `$global_sbatch_submit_retry_wait_seconds`
   (default 3 retries, 5s doubling backoff), overridable per-cluster.

## Complete log file inventory for the pipeline

Several independent log files get written to during a single submission, by
different layers — the harness watches **all** of them, not just `submit.log`,
since a failure can show up in any one of them and nowhere else:

| File | Written by | What ends up in it |
|---|---|---|
| `$home/etc/udp.log` (e.g. `/home/us3/lims/etc/udp.log`) | `write_log()` (`listen-config.php.template:42-49`), called via `write_logl()`/`write_logls()` in both `submitctl.php` and `submitone.php` | The daemons' own internal logging (poll cycles, state transitions, DB query failures) — this is `submitctl.php`/`submitone.php`'s "narration" of what they're doing, separate from any stdout capture |
| `$home/etc/submit.log` | shell redirect set up by `submitctl.php:342` (`php submitone.php ... >> $home/etc/submit.log 2>&1 &`) | Raw stdout+stderr of each spawned `submitone.php` process — includes its `echo`/`write_logl` lines, PHP warnings/fatals, and anything the included pages print outside output buffering |
| `$dumpfilebase/$lims_db-$ID.txt` (e.g. `/home/us3/lims/etc/submit/<db>-<ID>.txt`) | `submitone.php`'s `dump_it()` (submitone.php:308-313) via `ob_start("dump_it")` around each included page | Per-request capture of everything the included web pages (`queue_setup_1/2/3.php`, job-type pages) would have sent to a browser — this is what `check_cli_errors_in_dump()` (PR #27) scans for `ERROR:` lines |
| `$us3home/lims/etc/elog.txt` | `elog()` in `us3lims_dbinst/elog.php:11-19` | Only fires for messages containing the string `"queue_content"` — i.e. logging from `queue_content.php` (included as part of the queue-setup chain) and anything else that deliberately mentions queue_content. Easy to miss since it's conditional, not a general-purpose log |
| `/home/us3/lims/etc/elog2.txt` | `elog2()` in `us3lims_common/class/submit_local.php:11-14` | The actual `sbatch`/`qsub` submission attempts: command run, exit status, stdout line 0, retry/backoff messages, and the final FAILED/success outcome from `attemptSubmit()`/`submit_job()` — **this is the most direct evidence of an `sbatch` failure**, more so than `submit.log` |
| PHP's default error log (`php.ini` `error_log` destination — typically the web server's error log, e.g. Apache's `error.log`, since these pages are normally run in a web context) | bare `error_log($emsg)` calls with no explicit file, in `us3lims_common/class/jobsubmit.php:28` and `jobsubmit_aira.php:28` | Generic job-submission error messages from the base `jobsubmit` class — easy to miss because it's not one of the app's custom log files at all |
| `$priority_config['logfile']` (path defined in deployment-only `priority_config.php`, not in any repo) | `priority_log()` in `us3lims_common/class/priority.php:31-42` | Job priority/nice calculations — only written if `priority_config.php` exists and enables `'log' => true`; optional but worth checking for if it's configured on the target system |
| `$output_dir/$db-$requestID-messages.txt` | `cleanup.php` / `cleanup_gfac.php` (both the legacy root copies and the current `jobmonitor/` copies in `us3lims_gridctl`) | Post-completion cleanup messages (this is the file involved in an earlier-found GMP tarfile race condition) — not part of submission proper, but part of "the entire process" end-to-end since it covers what happens after a stage finishes |

## Design constraint: non-invasive, "work around" existing code

The harness must **never modify** `us3lims_gridctl` or `us3lims_common` source
(`submitctl.php`, `submitone.php`, `services.php`, `submit_local.php`, etc.) — no test
hooks, no patched copies. It's a standalone, additive tool in `us3lims_dbutils` that
observes/validates the existing system from outside (DB queries, log tailing,
filesystem/process inspection), so it can be dropped onto a current *or older*
deployed client machine as-is and used purely as a diagnostic. It doubles as a deep
debug/monitoring/validation tool, not just a pipeline runner.

Today autoflow/GMP only ever submits to `localhost` (`us3iab-node0`, local slurm, no
ssh) — but ssh-connected remote clusters are a near-future target, so the
fault-injection design stays extensible to "fake ssh" later even though v1 only
fakes local `sbatch`.

## Implementation: `uslims_autoflow_test.php`

Lives at the repo root, following the `uslims_autoflow.php`/`uslims_jobs.php`
argv-parsing + `utility.php` convention.

- `--check` (default, read-only): `services.php status`; lock-file PID/owner checks
  against `--expected-user` (default `us3`); writability of the shared log/lock
  *paths and files* (not just directories) computed from each file's actual
  owner/group/permission bits + `id -nG` group membership — **not** PHP's
  `is_writable()`, which silently lies when the harness itself runs as root;
  `elog.txt`/`elog2.txt` are checked against both `--expected-user` and
  `--web-user` (default `apache`) since browser-triggered submissions can write
  those directly; and a stale-`autoflowAnalysis`-rows query.
- `--run --invid <id> --rawid <id> --scenario <name>`: drives a fresh request via
  one of the `makeafrequest*.php` variants, then watches/tails it, interleaving
  every log file in the inventory above. `--gridctl-dir` defaults to
  `~us3/lims/bin` (where the daemon scripts and `autoflow_util/` are normally
  deployed) and only needs overriding for non-standard layouts.
- `--watch --reqid N`: same watch/tail behavior for an already-existing request.
- Both `--run` and `--watch` stop on `FINISHED`, `FAILED`, **or `WAIT`** (status
  comparisons are case-insensitive, since the column mixes daemon-set uppercase
  values like `READY`/`FINISHED` with lowercase job-state values like `wait`).
  `WAIT` is reported as an expected stop, not a hang/timeout — stages like
  `FITMEN` (`interactive="1"` in the analysis profile XML) genuinely require a
  human to act in the desktop client, so a `2dsa`-scenario run will always end at
  `WAIT` there. `--expect-status` (default `FINISHED`) accepts `WAIT`/`FAILED`
  as valid pass conditions for scenarios that are expected to land there.
- `--cleanup` (opt-in, `--run` only): after the watch ends regardless of outcome,
  deletes the `autoflow`/`analysisprofile`/`autoflowAnalysis` rows that `--run`
  itself created. Never applies to `--watch` (which targets a row it didn't
  create) and never fires automatically on `WAIT`, since a waiting request can be
  a real one someone still wants to finish by hand. Without `--cleanup`, prints
  the equivalent `DELETE` statements instead.
- `--fake-sbatch fail-always|fail-once` (opt-in, gated behind
  `--yes-i-know-this-restarts-submitctl`): writes a fake `sbatch` into `test_bin/`,
  restarts `submitctl.php` via the existing `services.php restart` with that dir
  prepended to `PATH`, runs the scenario, then restores the original `PATH`
  afterward. `test_bin/sbatch` and `test_bin/state/` are gitignored (regenerated each
  run); `test_bin/` itself is tracked so the directory exists.

## Status

- 2026-06-23: `--check` validated live on uslimstest; found `elog.txt` (0644,
  owner-writable by `us3` but not by `apache`) vs `elog2.txt` (0666, writable by
  both) — an inconsistency the original dir-only/`is_writable()` check would have
  missed entirely. Initially misread as a real gap because the default
  `--web-user apache` was wrong for this deployment (httpd workers here actually
  run as `us3`, per `ps -ef`) — fixed by auto-detecting the real non-root
  httpd/apache2/nginx/php-fpm process owner instead of hardcoding `apache`.
- 2026-06-23: `--run --scenario 2dsa` validated live on uslimstest end-to-end
  through real `sbatch` submission (job 280) and `SUBMITTED` status, with all
  logs correctly interleaved. A later `--run` attempt failed with `mkdir`/`cp`
  status 127 errors; root-caused to `/bin/mkdir`/`/bin/cp` having been
  overwritten by an accidental terminal paste (confirmed unrelated to this
  harness or any gridctl/common code) — resolved via `yum reinstall coreutils`.
- Reproduced the same `mkdir`/`cp` failure independently via the actual browser
  submission UI (no harness involved), which is what confirmed it was an OS-level
  binary corruption issue rather than anything in this tool or in
  `submit_local.php`.
- 2026-06-23: after the `coreutils` reinstall, `--run --scenario 2dsa --expect-status
  WAIT --cleanup` validated fully live end-to-end: `2DSA` and `2DSA_FM` both ran for
  real on the grid (`COMPLETE`, with real `maxrss`/runtime stats) and `submitctl.php`
  auto-advanced through both stages while the harness watched/tailed every log; it
  then correctly stopped at `WAIT` on `FITMEN` (reported as expected, not a hang),
  `RESULT: PASS` against `--expect-status WAIT`, and `--cleanup` removed the
  `autoflow`/`analysisprofile`/`autoflowAnalysis` rows it created. Full happy path
  (`--check`, multi-stage `--run`, `WAIT`-awareness, `--cleanup`) confirmed working
  together.
- Not yet validated live: `--fake-sbatch fail-always`/`fail-once`, non-2dsa
  scenarios (`pcsa`, `pcsa-onechannel`, `mc-cluster`, `cg`).

## Verification
- Run harness against a real GMP host for a normal `makeafrequest.php` 2DSA-chain
  request end-to-end, confirm it traces every status transition through to `WAIT`
  on `FITMEN` (the expected stopping point for this scenario) and exits 0 with
  `--expect-status WAIT`.
- Run with `--fake-sbatch fail-always`, confirm it traces through to `FAILED` with
  the expected `ERROR:`-derived `statusMsg`, exits non-zero, and that `scancel`
  logic in `submitctl.php` is not erroneously triggered (no real job was ever
  submitted).
- Run with `--fake-sbatch fail-once`, confirm the retry/backoff path in
  `submit_local.php::attemptSubmit()` is exercised and the job still ultimately
  succeeds.
- Run with `--cleanup`, confirm the `autoflow`/`analysisprofile`/`autoflowAnalysis`
  rows are actually removed; run without it, confirm the printed `DELETE`
  statements are correct and runnable as-is.
