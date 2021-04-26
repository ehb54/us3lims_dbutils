# us3lims_dbutils

## Various utilities for managing the database

 - general tools, can be used with any mysql database
   - php field_dep_tree.php
     - produces a dependency graph of foreign key constraints
     - general tool for this, can be used with any mysql database
   - php table_record_counts.php
     - returns a list of record counts

## UltraScan LIMS specific
 - uslims_dbs.php
   - lists all dbs with names like 'uslims3_%' except 'uslims3_global'
 - uslims_db_variables.php
   - lists global mysqld variables of interest
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
 - demo_data.sql.xz
   - contains demo1_veloc1 2.A.259 demo data which can be imported into a *fresh* dbinst
     - *fresh* since it may clobber existing data
   - unxz demo_data.sql.xz && mysql dbname < demo_data.sql

### dbmigrate notes
 - on the server to export
   - ```php stage0_metadata_dbhosts.php```
     - lists the dbname, dbhost & limshost in the database where status==completed 
   - ```php stage1_export_metadata.php dbhost```
     - extracts metadata, then you can edit, for example to manually remove dbinstance or adjust metadata
   - ```php stage2_export_databases.php dbhost```
     - extracts databases, config.phps and packages with metadata in a tar file
   - copy the ```export-full-dbhost.tar``` file to the server to import
 - on the server to import 
   - ```php -d mysqli.allow_local_infile=On stage3_import_databases.php export_dbhost this_dbhost ipaddress_of_this_dbhost```
     - the -d mysqli is needed for importing metadata from the xml
 - todo/notes
   - might require setting ```set global max_allowed_packet=64*1024*1024;``` in mysql as root to avoid big record issues.
     - or in my.cnf max_allowed_packet=64M or similar, but this requires a restart of the mysql server
   - admin people are not updated with new system specific data nor are people adjusted to our current admins
   - add validation of table_record_counts in package for comparison after import
   - make sure there is sufficient disk space on the drive where this repo is mounted to handle the .tar file
     - note the sql files remain .xz'd so perhaps a bit more than double the size of the tar file is sufficient (one for the tar file itself, one for the extracted files) 
