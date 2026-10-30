<?php
## Adds gfac.analysis.HPCAnalysisRequestID. Two clusters can issue the same
## Slurm job number, so a gfac.analysis row needs its own link back to the
## request it runs: monitor restarts and cleanup use it instead of guessing
## from the job number. Rows written before this keep NULL and fall back to
## the old lookup.
return [
    'id'            => 'gfac_analysis_request_id',
    'since_version' => '4.3.0',
    'scope'         => 'host',
    'description'   => 'Adds HPCAnalysisRequestID to gfac.analysis.',
    'run'           => function ( $gdb, $db, $apply, $context ) {
        global $changes;

        $has = schema_query( $gdb, "SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? "
                                 . "AND TABLE_NAME = 'analysis' AND COLUMN_NAME = 'HPCAnalysisRequestID'", $db );
        if ( $has && mysqli_num_rows( $has ) > 0 ) {
            report( 'ok', "$db.analysis has HPCAnalysisRequestID" );
            return;
        }

        $alter = "ALTER TABLE `$db`.analysis ADD COLUMN HPCAnalysisRequestID int(11) DEFAULT NULL AFTER us3_db";
        report( 'todo', "add $db.analysis.HPCAnalysisRequestID" );
        if ( !$apply ) {
            return;
        }
        if ( !gfac_runtime_table_alter_privileged( mysqli_grant_rows( $gdb ) ) ) {
            report( 'FAIL', "the gfac account cannot alter $db.analysis; as an account with ALTER on gfac run: $alter" );
            return;
        }
        if ( !mysqli_query( $gdb, $alter ) ) {
            report( 'FAIL', "$db.analysis: " . mysqli_error( $gdb ) );
            return;
        }
        $changes++;
        report( 'done', "added $db.analysis.HPCAnalysisRequestID" );
    },
];
