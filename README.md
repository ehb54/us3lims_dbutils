# us3lims_dbutils

## Upgrading a host

`uslims_upgrade.php` runs inside the usual upgrade sequence: check each checkout for local
changes with `uslims_git_info.php`, pull, build, run dbupgrade's stage1 and stage2, then
validate. That sequence starts from an idle host with gfac.analysis empty. Steps 1 to 5 below
get the host there for this release, since its jobs have to finish under the code that
started them. What `uslims_upgrade.php` itself checks and fixes is under its own entry below.

How to run these steps: log in to the LIMS host with ssh as your own account, then run
`sudo -i` for a root login shell (`module` and the builds need one). Run every step from that
shell. Paste each code block whole at its prompt and wait for it to finish before the next; do
not save a block to a file and run it as a script. Where a step says to run a command as us3 or
as a checkout's owner, run it from the same shell with `sudo -u <account>`. Answer the prompts
the tools ask (the backup tool and stage2 ask y/n). The blocks call `command cp`, so root's
`cp -i` alias does not stop them.

1. Let any dispatch request settle first, with Resolve or Close on the dispatch dashboard for
   a held one: that needs the web server and services running. Then stop the web server (or
   otherwise block access), so nothing can write to any LIMS database while the upgrade runs.
   Take the gridctl services out of service too, so nothing is submitted while the code
   moves: `php services.php stop` in `~us3/lims/bin`, then comment out the LIMS cron lines,
   wherever they are (`/etc/crontab`, us3's crontab or `/etc/cron.d`).
2. Check each checkout for local changes as root: `php uslims_git_info.php` (`--diff-report`
   shows them). Before resolving them, record as root each checkout's commit, branch and local
   changes, staged and not, and this repo's `db_config.php`, which "Backing out a failed upgrade"
   puts back. This finds the checkouts where `uslims_git_info.php` does, following symlinks as it
   does:

   ```
   mkdir -p ~us3/lims/etc/pre-upgrade-changes
   command cp ~us3/lims/database/utils/db_config.php ~us3/lims/etc/pre-upgrade-db_config.php
   find -L ~us3/lims/bin ~us3/lims/database ~us3/us3-nodb /opt/ultrascan3 /srv/www/htdocs \
       -name .git 2>/dev/null | while read -r g; do readlink -f "${g%/.git}"; done | sort -u |
     while read -r d; do
       p=~us3/lims/etc/pre-upgrade-changes/"$(echo "$d" | tr / _)"
       git -c safe.directory='*' -C "$d" diff --cached --binary > "$p.staged.diff"
       git -c safe.directory='*' -C "$d" diff --binary > "$p.diff"
       echo "$d $(git -c safe.directory='*' -C "$d" rev-parse HEAD) $(git -c safe.directory='*' -C "$d" branch --show-current)"
     done > ~us3/lims/etc/pre-upgrade-checkouts.txt
   ```

   Then resolve the local changes, as for any release, without hiding them in git, and leave none
   staged either: step 6's `--update-pull` refuses a checkout with unstaged edits, which
   `uslims_git_info.php` reports, and step 6's checkout stops at a staged change to a file the
   release changes.
3. Move this repo only to the new release, as us3: `git fetch`, `git checkout <release branch>`,
   then `git pull --ff-only`. This works from the release branch, another branch or a tag.
   Keep a copy of this README outside the checkout (`cp README.md ~us3/lims/etc/upgrade-README.md`):
   once a backout's step 2 has run, this repo is the old release's, which has no backout section.
4. Finish every job. `php uslims_jobs.php --busy` lists each one and what to do; repeat
   until it says idle. This has to come before gridctl and common move: this release's
   monitors don't run until `--apply` has converted `listen-config.php`, so the code that
   started the jobs has to finish them. If gridctl and common were already moved, check
   out the release that started the jobs in both, finish the jobs, and move them again.
   `--busy` also names any instance whose name this release can't run; it stays busy until
   that instance is retired: `--busy` prints the commands (dump, then drop the database and
   its newus3.metadata row, then move its directory out of the web root).
5. Take a full backup of the whole database server (`uslims_db_binary_backup.php`, or your
   own backup process), now that the finished jobs are imported. "Backing out a failed
   upgrade" restores it; stage1's export below covers only the instances, not gfac or newus3.
6. Pull the rest to the new release and build, as for any release: common, every instance's
   dbinst, gridctl, webinfo, newinst and us3_sql, and the UltraScan checkouts. First set each
   repository's entry in this repo's `db_config.php` `$repo_branches` to its release branch:
   `uslims_git_info.php` reports against it and `--update-branch` checks it out (`--update-pull`
   pulls whatever is checked out), newinst clones new instances from it, and
   `uslims_upgrade.php` step 0 prints the commands that move a missed checkout to it. In each checkout, as its
   owner, `git fetch`, then `git checkout <its release branch>`; then, as root from
   a login shell (`sudo -i`, which the builds' `module` needs), `php uslims_git_info.php
   --update-pull all --update-pull-build` pulls each to its branch's head and builds. The code has to be in place before the next steps.
7. Run dbupgrade's stage1 and stage2 (see dbupgrade below), from `dbupgrade/` as root with its
   `my.cnf`. Stage1 reads its `[mysqldump]` section and stage2 its `[client]` section; if
   `dbupgrade/my.cnf` doesn't exist yet, this creates it, mode 600, asking for MariaDB root's
   password:

   ```
   [ -e ~us3/lims/database/utils/dbupgrade/my.cnf ] || ( umask 077
     read -rsp 'MariaDB root password: ' pw; echo
     printf '[%s]\npassword=%s\nmax_allowed_packet=256M\n' mysqldump "$pw" client "$pw" \
       > ~us3/lims/database/utils/dbupgrade/my.cnf )
   ```

   Then, in `dbupgrade/`, `php stage1_export_dbinsts.php <dbhost>`, then `php
   stage2_import_dbinsts.php <dbhost>`. Stage2 rebuilds every instance from the release's
   us3.sql, which is what puts each one on the release's schema: tables, columns, enum values
   and stored procedures.
8. `php uslims_upgrade.php --check` as root to see what it would do, then `--apply` to make
   the changes. Its step 9 refuses an instance that lacks anything us3.sql defines, naming
   stage1 and stage2: it adds only this release's own tables and columns in place. Where the
   gfac account can't create gfac's dispatch tables or alter gfac.analysis, `--check` prints
   the command for each, and root runs what it prints, then runs `--check` again. The first
   reads MariaDB root's password from `DBPW`; set it without echoing it first, with
   `read -rsp 'MariaDB root password: ' DBPW; export DBPW`, and `unset DBPW` after. Run the
   `ALTER TABLE` it prints with `mysql -u root -p -e '<the statement>'`.
9. Finish as `--apply` prints at its end: uncomment the LIMS cron lines you commented out in
   step 1, except the gridctl lines `--apply` retired (the others, such as
   `cluster_status.php` and the nightly backup, stay off until you do, and a later run of the
   roles' play leaves them off too), then start the services as us3.
10. Validate: restart the web server, confirm the host is actually serving again, and test
    (log in, submit a job, check queue_viewer.php) before considering the host upgraded.

Steps 1-7 happen before `--apply` ever runs, with nothing serving requests in between.
That's what makes step 9 of `--apply` (the UTC migration) and every config rewrite in
between safe: there is no window where new code could write a row, or read a config
file, before the migration has run. `uslims_upgrade.php` doesn't defend against that
window itself; its own preflight (step 1) checks that the host is idle (no jobs, no
other DB client, cron entries commented out) the same way, without also needing to
account for live web traffic, because this procedure already guarantees that.

No separate script is needed for a host starting from an older release than the
previous one, or several releases behind at once: every step in `uslims_upgrade.php`
checks the host's own current state and decides whether it has anything to do, rather
than assuming a specific starting point. A host several releases behind just sees more
`todo`s in one `--apply`, not a different tool.

## Backing out a failed upgrade

For an emergency only: it puts the host back on the release it ran before, with the code,
the databases and every file `uslims_upgrade.php --apply` changed. Restoring the backup
discards everything written since step 5 of "Upgrading a host", including any job submitted on
the new release. Run it the way "Upgrading a host" says, from the same root login shell.

1. Stop the web server and the gridctl services (`php services.php stop` as us3 in
   `~us3/lims/bin`, or `systemctl stop us3-listen`), and comment out the LIMS cron lines.
2. Put db_config.php back from the copy step 2 of "Upgrading a host" saved, and every checkout
   back as that step recorded it, at its commit with its local changes, staged and not, as root.
   Then the files git wrote are given to each checkout's owner, once all are back, since a
   submodule's git directory sits inside its parent's; files git ignores, such as the database
   backup and `dbupgrade/my.cnf`, stay root's:

   ```
   command cp ~us3/lims/etc/pre-upgrade-db_config.php ~us3/lims/database/utils/db_config.php
   f=
   while read -r d c b; do
     p=~us3/lims/etc/pre-upgrade-changes/"$(echo "$d" | tr / _)"
     g() { git -c safe.directory='*' -C "$d" "$@"; }
     g checkout -q -f "$c" &&
       { [ ! -s "$p.staged.diff" ] || g apply --index --binary "$p.staged.diff"; } &&
       { [ ! -s "$p.diff" ] || g apply --binary "$p.diff"; } || { echo "FAILED: $d"; f=1; }
   done < ~us3/lims/etc/pre-upgrade-checkouts.txt
   while read -r d c b; do
     find "$d" -user root -newer ~us3/lims/etc/pre-upgrade-checkouts.txt | while read -r f; do
       git -c safe.directory='*' -C "$d" check-ignore -q "$f" 2>/dev/null || chown -h "$(stat -c %U:%G "$d")" "$f"
     done
   done < ~us3/lims/etc/pre-upgrade-checkouts.txt
   [ -z "$f" ]
   ```

   A `FAILED:` line names a checkout not put back, and the block then returns non-zero. A stale
   `.git/index.lock` leaves it as it was; a file in the way of one its saved changes create
   leaves it at its commit without its saved changes. Fix that, then run the whole block again.

   Then build the UltraScan checkouts without pulling, as `uslims_git_info.php` does: as root
   from a login shell, which `module` needs, then each given to the owner it uses. This uses the
   host's core count; `uslims_git_info.php` uses the Slurm node's:

   ```
   su - -c 'cd ~us3/us3-nodb && module swap ultrascan/mpi-build && ./make-uma build && ./make-uma install && chown -R us3:us3 ~us3/us3-nodb/'
   su - -c 'cd /opt/ultrascan3 && module swap ultrascan/gui-build && ./makeall.sh -j$(nproc) && ./makesomo.sh -j$(nproc) && chown -R usadmin:usadmin /opt/ultrascan3/'
   ```

   `uslims_git_info.php` can't do this: it pulls first, and stops at a checked-out commit. Each
   checkout is now at a detached commit; a next attempt's steps 3 and 6 move it from there. To
   take routine updates on the old release instead, first put each checkout on the branch the
   record names for it (its third field; for one recorded detached, its repository's entry in
   `db_config.php`'s `$repo_branches`) at its recorded commit, as its owner,
   `git checkout -B <branch> <commit>`: 4.2.0's `--update-pull` stops at a detached
   checkout. A branch that also carries the new release, such as dbutils' `main` or UltraScan's
   `master`, then pulls the new release again.
3. Restore the full backup from step 5 of "Upgrading a host", with its tool's own restore
   (for `uslims_db_binary_backup.php`: stop MariaDB, replace its data directory with the
   backup, as the script printed, and start it again). It returns every instance as it
   was before stage2 rebuilt it, and undoes everything `--apply` did in the databases: the
   people_audit UTC conversion, the stored procedures, the dispatch tables in gfac and each
   instance, gfac.analysis.HPCAnalysisRequestID and gfac.runtime_prediction. Request ids are
   then given out again, so remove the cluster work directory of each job submitted after the
   backup, such as step 10's test job: a job reusing its id would extract its input over the old
   files and fail. Each is `<db>-<request id as six digits>` (`uslims3_x-000003`) under the old
   listen-config.php's `$work_remote`, or the cluster's `workdir`; those to remove are numbered
   above the restored instance's `SELECT MAX(HPCAnalysisRequestID) FROM HPCAnalysisRequest`.
4. Put back the files `--apply` changed, and delete the ones it created. Each run of `--apply`
   copies the files it changes into its own directory,
   `~us3/lims/etc/uslims_upgrade-backup-<time>-pid<pid>`, and lists the files it creates in
   that directory's `created.txt`. Use only this attempt's directories, the ones newer than the
   checkouts record:

   ```
   find ~us3/lims/etc -maxdepth 1 -name 'uslims_upgrade-backup-*' \
     -newer ~us3/lims/etc/pre-upgrade-checkouts.txt | sort
   ```

   For each file, the oldest of these directories that names it decides: a copy there is the
   original, so put it back; a `created.txt` entry means the host didn't have it, so delete
   it (on Debian, `a2disconf lims-csp` or `a2disconf lims-common-alias` first for those two).

   | Copy in the backup directory | Put back as |
   |---|---|
   | `global_config.php` | `/srv/www/htdocs/common/global_config.php` |
   | `listen-config.php`, `cluster_config.php` | `~us3/lims/bin/` |
   | `db_config.php` | not from here: step 2 put back its pre-upgrade copy |
   | `config.default` | webinfo's `config.php` (`/srv/www/htdocs/uslims3/config.php`) |
   | `config.<name>` | the `config.php` (or its overlay) of the instance or newinst `<name>` under `/srv/www/htdocs` that the run's output names |
   | `dbinst-base.v1.php` | `~us3/lims/etc/config/dbinst-base.v1.php` |
   | `runtime_advisory_<family>.json` | `~us3/lims/etc/` |
   | `known_hosts.<account>`, `authorized_keys.<account>` | that account's `~/.ssh/` |
   | `us3.crontab` | us3's crontab: `crontab -u us3 us3.crontab` |
   | `crontab`, any other cron file | `/etc/crontab`, or `/etc/cron.d/<name>` |
   | `lims-common-alias.conf` | `/etc/httpd/conf.d/`, or `/etc/apache2/conf-available/` then `a2enconf lims-common-alias` on Debian |
5. Nothing else in Apache needs undoing: step 4 removed the confs `--apply` installed and put
   back one it replaced or removed. The web server starts again in step 7.
6. What else `--apply` made can stay, since a release before 4.3.0 doesn't use it or, for
   `elog.txt`, appends to it: the circuit-breaker, dispatch spool and ssh-control directories,
   `~us3/lims/etc/elog.txt` and its HMAC key, the accounts' `id_ed25519` keys, and
   `~us3/lims/etc/config/instances/`. Move dbupgrade's export files (`dbupgrade/export-*`) aside
   before a next attempt, whose stage1 refuses while they are there.
7. Uncomment the LIMS cron lines you commented out in step 1 here and in step 1 of "Upgrading
   a host", including the gridctl lines in us3's crontab that step 4 put back still commented,
   and remove the dispatch heartbeat line if step 9 of the upgrade added one (the old gridctl
   doesn't have it). Then start the old release's services with
   `systemctl restart us3-listen` (a plain start does nothing: the unit stays active after
   `services.php stop`), or `php services.php start` as us3 on a host without that unit. Start
   the web server. Test as in step 10 of "Upgrading a host": log in, submit a job, and watch it
   finish in queue_viewer.php.

## Various utilities for managing the database

 - general tools, can be used with any mysql database
   - php field_dep_tree.php
     - produces a dependency graph of foreign key constraints
     - general tool for this, can be used with any mysql database
   - php table_record_counts.php
     - returns a list of record counts

## UltraScan LIMS specific
 - uslims_db_binary_backup.php
   - creates a binary backup of the mysql database in a unique directory
 - uslims_certs.php
   - reports on https cert and scigap expiration dates
 - uslims_db_schemas.php
   - creates diff-able schema dumps with stored procedures, events and triggers for each dbinstance found in the database
 - uslims_db_variables.php
   - lists global mysqld variables of interest
   - also lists status of Threads_connected & Max_used_connections
 - uslims_dbs.php
   - lists all dbs with names like 'uslims3_%' except 'uslims3_global'
   - optionally lists times of last update - both from database and the innodb files themselves (see --help for details) 
 - uslims_domain_info.php
   - examines and validates domain name information
     1. checks os hostname
     2. checks newus3.metadata dbhost & limshost for status==completed
     3. compares mysql uslims3_% databases with newus3.metadata completed hosts
     4. checks global and dbinstance config.phps
     5. reports on ```$class_dir```s used
     6. checks listen-config.php
     7. checks httpd configs
   - optionally changes the domain name by updating the above appropriately and providing sudo commands to finalize change
     - includes support for both certbot and self-signed certs.
     - ```php uslims_domain_info.php --change old_domain_name new_domain_name```
       - old_domain_name is required for metadata & httpd config changes.
   - optionally sets up redirects
 - uslims_upgrade.php
   - moves an existing host to the Slurm submission contract; run as root at step 8 of "Upgrading a host" above, once the jobs are finished, every repo has moved and stage2 has rebuilt the instances
   - host configuration only: the stack code and the database schema are upgraded separately, and the code must be in place first
     0. checks that every stack checkout (us3_sql included) is at 4.3.0 or newer, read from each repository's VERSION, and refuses to go further while one is older (each instance docroot is its own dbinst clone, so each is checked on its own)
     1. refuses to run unless the host is idle: none of listen, manage-us3-pipe, submitctl, dispatchone or esign running (however they were started, not only via the us3-listen unit), no jobmonitor and no unfinished gfac.analysis row, no unsettled dispatch request, no cleanup claim, an empty local Slurm queue, no node allocated, no other client on MariaDB, nobody else logged in, and the LIMS cron entries commented out; every failing check is reported before it stops
     2. rewrites listen-config.php from the gridctl template, carrying the site's values
     3. deactivates cluster entries the Slurm code cannot submit to (metascheduler entries, and anything whose submittype is not slurm), or with --convert-http converts the national HPC entries left over from Airavata (active or not) to the template's SSH shape -- conversion never activates an entry or its status probe, deliberately -- then sets the global_config.php settings the new code requires (both stall timers 0, tenant scope, local cluster, env_script_lines per cluster, and single_node with maxproc <= ppn <= CPUs on one-node appliances)
     4. records the host key for each active cluster and any cluster named with --activate that isn't active yet, and checks ssh for us3 and the web account (the PHP-FPM pool user); authorizes an account's own key too, but only for the host's own local cluster -- a remote cluster still needs that account's key created if missing and authorized for login by hand, which --activate's own message says when it applies
     5. creates the shared circuit-breaker directory, the shared ssh-control directory, elog.txt and its HMAC key (shared from the start so a split web/us3 account host does not depend on whichever account touches them first), the gfac.runtime_prediction table the runtime-advisory pilot uses if it is ever turned on -- including, for a pilot table created without prediction_cluster/prediction_cluster_source, the additive column migration (runtime_pilot_table_add_prediction_cluster.sql) those two columns need ; it no longer creates the retired job-count selector's tables (metascheduler_decision, cluster_status_nudge), and an existing decision table is kept as history
     6. removes the gridctl cron entries from the crontabs (us3's, /etc/crontab, /etc/cron.d), under gridctl.php and the gridctl_pro/dev names before it, since each job's jobmonitor now carries it to a terminal state
     7. installs the util/csp policy as Report-Only in Apache (lims-csp.conf) unless a policy is already configured; enforcing it is a later step
     8. verifies the result, including that every active cluster passes the submission sizing gate rather than refusing every job
     9. per instance: migrates people_audit.created_at to UTC if not already done, using the running config.php (or overlay) each instance already connects with -- idempotent, so a host dbupgrade/stage1+stage2 already migrated is reported 'ok' here, not migrated again; creates the dispatch tables; checks that the instance has every table, column and enum value us3.sql defines, and if not, FAILs it, naming stage1 and stage2, and loads no procedures there; loads the release's stored procedures. Host-level: the dbinst base (dbinst-base.v1.php) and `instances/` under `~us3/lims/etc/config` that newinst needs to make an instance, made from the instances' and newinst's configs as the roles make them, when the host has none (with no instance yet, dbinst's contract comes from a temporary clone at db_config.php's dbinst entry); db_config.php's `$repo_branches` dbinst entry, set to the branch or tag the instances run, since newinst clones a new instance from it; the gfac dispatch tables and gfac.analysis.HPCAnalysisRequestID. The idle check refuses a host with any row in gfac.analysis, since a finished cleanup deletes its row; a row without a request that reaches this step is listed and kept, and no monitor acts on it
     10. runs dbinst's read-only installation check (tools/check_installation.php) and reports what needs the operator
   - ```--activate cluster[,cluster]``` brings a converted entry live: sets active=true in global_config.php and turns on its cluster_config.php probe, together, once its env_script_lines are real and ssh to it succeeds (as us3, and as the web account if different). --activate checks ssh before step 4, which records the host key (and, for the host's own cluster, authorizes the key). So the first --apply --activate for a cluster records its host key, and the next one activates it -- not only for a cluster converted in this very run, but any cluster whose host key is not recorded yet. For a remote cluster, authorize the account's own key on the cluster by hand first; step 4 only ever records that cluster's host key, never authorizes this account's key on the far end
   - ```--site-admin-email address``` sets who receives the site's submission-failure and outage notices. Without it the address comes from listen-config.php's ```$admin_email```, except on a host built from the roles' main branch, whose listen-config.php still has their former default (us3-admin@biophysics.uleth.ca): that is reported as input to supply, and the given address replaces it in listen-config.php too. The idle check treats a host that global_config.php records as shared as shared, without ```--single-tenant no```
   - ```--runtime-advisory dir|off``` turns the runtime advisory pilot on or off in step 3. With a directory it checks each runtime_advisory_<family>.json there (2DSA, GA, PCSA) with common's own loader, installs it to ~us3/lims/etc as 0640, owned by us3 and the web account's primary group, when the installed file differs, and sets ```$global_runtime_advisory_enabled = true```. Nothing is installed and the switch is left alone while any artifact fails, or while gfac.runtime_prediction is missing and the gfac account can't create it (the FAIL names the command to create it by hand). ```off``` sets it false and leaves the files. Step 5 creates the table when the gfac account can; while the advisory is on, a table it can't create or complete is a FAIL
   - ```--prediction-cluster cluster=name``` (repeatable) sets a cluster's prediction_cluster_name, the cluster name the model scores it as, for a cluster whose own name the model does not know; a name with no code in common's runtime_cluster_map.php is refused
   - dry run by default; ```php uslims_upgrade.php --apply``` makes the changes, backing up each file first; safe to rerun. See "Upgrading a host" above for the actual procedure this fits into, and "Backing out a failed upgrade" for the way back
 - uslims_git_info.php
   - for all expected and discovered repos, reports path, url, branch, use, rev#, rev date, local changes, and deltas
   - also allows updating to expected branches, git pull, and build (for gui & mpi)
   - optional "diff report" (see --help for details)
 - uslims_jobs.php
   - reports on jobs
     - summary report for db
     - detailed reports by gfacID or HPCAnalysisRequestID
     - e.g. monitor a running job
       - ```php uslims_jobs.php --db uslims3_DBINSTNAME --gfacid GFACID --monitor```
     - see --help for full command line options
 - uslims_people.php
   - lists and optionally updates dbinsts.people from newus3.people
   - optional administrator report (lists people with userlevel+advancelevel >= 3 )
 - demo_data.sql.xz
   - contains demo1_veloc1 2.A.259 demo data which can be imported into a *fresh* dbinst
     - *fresh* since it may clobber existing data
   - unxz demo_data.sql.xz && mysql dbname < demo_data.sql
 - uslims_update_notice.php
   - syncs us3_notice.notice with a remote server
### backups/rsync
 - uslims_daily_backup.php
   - creates sql.gz for all dbinsts
   - optionally calls uslims_daily_rsync.php
   - optionally sends email report with statistics
 - uslims_daily_rsync.php
   - runs rsync of daily backups   
### dbmigrate
 - use : move dbinstances from one server to another
 - on the server to export
   - ```php stage0_metadata_dbhosts.php```
     - lists the dbname, dbhost & limshost in the database where status==completed 
   - ```php stage1_export_metadata.php dbhost```
     - extracts metadata, then you can edit, for example to manually remove dbinstance or adjust metadata
     - optionally extracts named databases (see --help for usage details)
   - ```php stage2_export_databases.php dbhost```
     - extracts databases, config.phps and packages with metadata in a tar file
     - optionally renames databases (see --help for usage details)
   - copy the ```export-full-dbhost.tar``` file to the server to import
     - can also be used in-place in combination with renaming
 - on the server to import 
   - ```php -d mysqli.allow_local_infile=On stage3_import_databases.php export_dbhost this_dbhost ipaddress_of_this_dbhost```
     - the -d mysqli is needed for importing metadata from the xml
   - ```php uslims_people.php --update```
     - to set the newus3.people and pws in the newly created databases
   - ```php uslims_permissions.php --grant-integrity dbname --grant-integrity-fix```
     - restores each moved dbinstance's grants, including PAM user accounts, on this host (repeat --grant-integrity per dbname)
 - todo/notes
   - admin people are not updated with new system specific data nor are people adjusted to our current admins
   - add validation of table_record_counts in package for comparison after import
   - make sure there is sufficient disk space on the drive where this repo is mounted to handle the .tar file
     - note the sql files remain .xz'd so perhaps a bit more than double the size of the tar file is sufficient (one for the tar file itself, one for the extracted files) 

### dbupgrade
  - use: upgrade existing dbinstances on a server to the latest us3_sql sql & procedures 
  - stage1_export_dbinsts.php
    - exports all dbinstances and record counts
  - stage2_import_dbinsts.php
    - drops dbinstance databases, creates new from latest us3_sql, imports stage1 exported data, compares record counts
    - before the drop, checks every dump's tables (empty ones included) and columns against the latest us3_sql schema and stops, dropping nothing, if any is missing (stage3_import_databases.php does the same before creating any dbinstance)
    - the dispatch tables (dispatch_request, dispatch_attempt, dispatch_event) are defined once in us3_sql's us3_dispatch.sql, which us3.sql loads and uslims_upgrade.php applies in place, and dbmigrate exports them even when its schema rev predates them
    - converts each dbinstance's people_audit.created_at to UTC from the zone the original exporting host actually wrote those rows in, captured by stage1 into a per-dbinstance timezone file and carried across the export/import round trip -- falls back to the stage2 connection's own session time zone for a package made before this file existed, once per dbinstance -- each row's created_at_utc flag travels with the data, so a later re-import does not shift already-correct rows again; a named zone always converts row-by-row in PHP (DateTimeZone), since CONVERT_TZ() returns NULL without the server's time zone tables -- no zone tables and no privilege on mysql.time_zone_name required
    - a row actually written during the repeated local hour when clocks fall back (e.g. 1:30am occurring twice) is ambiguous by nature; accepted, since the original data carries no way to tell the two occurrences apart. Which occurrence a conversion resolves it to depends on the path, and is not uniform across zones: confirmed directly (MariaDB, named zone 'America/Chicago', west of UTC) CONVERT_TZ() resolves it to daylight time (the first occurrence), not standard, so a row genuinely written during the standard-time occurrence converts up to 60 minutes off. The default zone, 'SYSTEM', resolves the same ambiguous hour the other way on that same host (confirmed directly: a server with its OS zone set to 'America/Chicago' reads @@system_time_zone as 'CDT' but CONVERT_TZ(..., 'SYSTEM', '+00:00') against the repeated 01:30 applies the -6h CST offset, not -5h CDT) -- so for SYSTEM there, it is the daylight-time occurrence that converts up to 60 minutes off instead. A named zone east of UTC does not necessarily follow 'America/Chicago''s pattern either: PHP's own DateTime (the path a named zone actually takes -- see above) resolves 'Europe/Berlin''s repeated hour (its clocks fall back in October) to standard time (the second occurrence), confirmed directly, the opposite of 'America/Chicago''s CONVERT_TZ result. It is a real, accepted gap either way, just not a single, predictable direction
  - uslims_upgrade.php's step 9 runs this same people_audit UTC migration in place, against every LIMS instance already running on the host, using each instance's own config.php -- no new credentials
  - step 9 also loads the release's stored procedures (us3_procedures.sql) into each instance whose procedures are not this release's, with the instance's own account; _dbutils_procedures_checksum() in the instance records the release's checksum and a fingerprint of the routines as loaded, so a later change to them (a rollback) is reloaded
  - migrate_people_audit_utc.php runs the migration by itself, with none of uslims_upgrade.php's or stage2's other machinery: for a host already upgraded in-place before step 9 existed, or to fix one dbinstance by hand
 
