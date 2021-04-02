# us3lims_dbutils

## Various utilities for managing the database

 - general tools, can be used with any mysql database
   - php field_dep_tree.php
     - produces a dependency graph of foreign key constraints
     - general tool for this, can be used with any mysql database
   - php table_record_counts.php
     - returns a list of record counts
     
 - UltraScan specific
   - demo_data.sql.xz
     - contains demo1_veloc1 2.A.259 demo data which can be imported into a *fresh* dbinst
       - *fresh* since it may clobber existing data
     - unxz demo_data.sql.xz && mysql dbname < demo_data.sql

