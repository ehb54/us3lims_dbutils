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
 - uslims_db_schemas.php
   - creates diff-able schema dumps with stored procedures, events and triggers for each dbinstance found in the database
 - uslims_db_variables.php
   - lists global mysqld variables of interest
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
 - uslims_git_info.php
   - for all expected and discovered repos, reports path, url, branch, use, rev#, rev date, local changes, and deltas
   - also allows updating to expected branches, git pull, and build (for gui & mpi)
 - uslims_people.php
   - lists and optionally updates dbinsts.people from newus3.people
   - optional administrator report (lists people with userlevel+advancelevel >= 4 )
 - demo_data.sql.xz
   - contains demo1_veloc1 2.A.259 demo data which can be imported into a *fresh* dbinst
     - *fresh* since it may clobber existing data
   - unxz demo_data.sql.xz && mysql dbname < demo_data.sql

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
 
