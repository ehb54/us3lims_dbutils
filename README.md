# us3lims_dbutils

## Various utilities for managing the database

 - general tools, can be used with any mysql database
   - php field_dep_tree.php
     - produces a dependency graph of foreign key constraints
     - general tool for this, can be used with any mysql database
   - php table_record_counts.php
     - returns a list of record counts

## UltraScan LIMS specific
 - uslims_binary_db_backup.php
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
   - moves an existing host to the Slurm submission contract; run as root after pulling common, every instance, gridctl and dbutils
   - host configuration only: the stack code and the database schema are upgraded separately, and the code must be in place first
     0. checks that every stack checkout is at 4.3.0 or newer, read from each repository's VERSION, and refuses to go further while one is older (each instance docroot is its own dbinst clone, so each is checked on its own)
     1. refuses to run unless the host is idle: none of listen, manage-us3-pipe, submitctl or esign running (however they were started, not only via the us3-listen unit), no jobmonitor and no unfinished gfac.analysis row, no cleanup claim, an empty local Slurm queue, no node allocated, no other client on MariaDB, nobody else logged in, and the LIMS cron entries commented out; every failing check is reported before it stops
     2. rewrites listen-config.php from the gridctl template, carrying the site's values
     3. deactivates cluster entries the Slurm code cannot submit to (metascheduler entries, and anything whose submittype is not slurm), or with --convert-http converts the national HPC entries left over from Airavata (active or not) to the template's SSH shape -- conversion never activates an entry or its status probe, deliberately -- then sets the global_config.php settings the new code requires (both stall timers 0, tenant scope, local cluster, env_script_lines per cluster, and single_node with maxproc <= ppn <= CPUs on one-node appliances)
     4. records the host key for each active cluster and any cluster named with --activate that isn't active yet, and checks ssh for us3 and the web account (the PHP-FPM pool user); authorizes an account's own key too, but only for the host's own local cluster -- a remote cluster still needs that account's key created if missing and authorized for login by hand, which --activate's own message says when it applies
     5. creates the shared circuit-breaker directory, the shared ssh-control directory (common#24), elog.txt and its HMAC key (dbinst#75, shared from the start so a split web/us3 account host does not depend on whichever account touches them first), and the gfac.runtime_prediction table the runtime-advisory pilot uses if it is ever turned on
     6. removes the gridctl cron entries from the crontabs (us3's, /etc/crontab, /etc/cron.d), under gridctl.php and the gridctl_pro/dev names before it, since each job's jobmonitor now carries it to a terminal state
     7. verifies the result
   - ```--activate cluster[,cluster]``` brings a converted entry live: sets active=true in global_config.php and turns on its cluster_config.php probe, together, once its env_script_lines are real and ssh to it succeeds (as us3, and as the web account if different). A cluster converted in the same --apply run may need a second --apply before --activate succeeds: step 4 is what actually sets a key up, and it runs after this check. For a remote cluster, step 4 only records its host key; the account's own key still needs creating (if missing) and authorizing for that cluster's login by hand before either --apply run will help
   - dry run by default; ```php uslims_upgrade.php --apply``` makes the changes, backing up each file first; safe to rerun
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
 
