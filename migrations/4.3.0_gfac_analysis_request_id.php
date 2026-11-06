<?php
## Lists the rows without a request, which no monitor acts on and no code guesses at.
if ( !function_exists( 'gfac_analysis_report_rows_without_request' ) ) {
    function gfac_analysis_report_rows_without_request( $gdb, $db ) {
        if ( !preg_match( '/^\w+$/', (string) $db ) ) {
            report( 'FAIL', "invalid gfac database name '$db'" );
            return;
        }
        $rows = mysqli_query( $gdb, "SELECT id FROM `$db`.analysis WHERE HPCAnalysisRequestID IS NULL ORDER BY id" ); // NOSONAR $db is validated above
        if ( !$rows ) {
            report( 'FAIL', "$db.analysis: could not read rows without a request: " . mysqli_error( $gdb ) );
            return;
        }
        $ids = array_column( mysqli_fetch_all( $rows, MYSQLI_ASSOC ), 'id' );
        if ( $ids ) {
            report( 'note', "$db.analysis: " . count( $ids ) . " row(s) have no request, so no monitor acts on them:"
                          . " id " . implode( ', ', array_slice( $ids, 0, 20 ) )
                          . ( count( $ids ) > 20 ? ' and ' . ( count( $ids ) - 20 ) . ' more' : '' )
                          . ". php uslims_jobs.php --close-job <id> as us3 lists any open result that could be its job, for you to choose." );
        }
    }
}
## Adds gfac.analysis.HPCAnalysisRequestID. Two clusters can issue the same
## Slurm job number, so a gfac.analysis row needs its own link back to the
## request it runs: monitor restarts and cleanup use it instead of guessing
## from the job number. The idle check refuses a host with any row, so one from
## before this reaches here only by another path: it is listed and kept, and no
## runtime code acts on a row without its request.
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
            gfac_analysis_report_rows_without_request( $gdb, $db );
            return;
        }

        $alter = "ALTER TABLE `$db`.analysis ADD COLUMN HPCAnalysisRequestID int(11) DEFAULT NULL AFTER us3_db";
        ## Checked in --check too, so --check reports what --apply would fail on.
        if ( !gfac_runtime_table_alter_privileged( mysqli_grant_rows( $gdb ) ) ) {
            report( 'FAIL', "the gfac account cannot alter $db.analysis; as an account with ALTER on gfac run: $alter" );
            return;
        }
        report( 'todo', "add $db.analysis.HPCAnalysisRequestID" );
        if ( !$apply ) {
            return;
        }
        if ( !mysqli_query( $gdb, $alter ) ) {
            report( 'FAIL', "$db.analysis: " . mysqli_error( $gdb ) );
            return;
        }
        $changes++;
        report( 'done', "added $db.analysis.HPCAnalysisRequestID" );
        gfac_analysis_report_rows_without_request( $gdb, $db );
    },
];
