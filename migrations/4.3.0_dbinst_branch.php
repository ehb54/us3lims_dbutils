<?php
## newinst clones a new instance's dbinst from db_config.php's $repo_branches, which the roles write
## only on a first install; after an upgrade it still names the old release, whose dbinst has no
## config loader. Set it to the ref this host's instances run (a branch, or the tag they are at).

## The ref one dbinst checkout runs, or '' when it is on neither a branch nor a tag.
function dbinst_checkout_ref( $dir ) {
    $git = 'git -c safe.directory=' . escapeshellarg( '*' ) . ' -C ' . escapeshellarg( $dir );
    foreach ( [ "$git symbolic-ref -q --short HEAD", "$git describe --tags --exact-match HEAD" ] as $cmd ) {
        list( $out, , $rc ) = capture( $cmd );
        if ( $rc === 0 && trim( $out ) !== '' ) {
            return trim( $out );
        }
    }
    return '';
}

return [
    'id'            => 'dbinst_branch',
    'since_version' => '4.3.0',
    'scope'         => 'host',
    'description'   => "Sets db_config.php's dbinst entry in \$repo_branches to the ref this host's instances run.",
    'run'           => function ( $db_handle, $db, $apply, $context ) {
        $config = $context[ 'db_config' ];
        $text   = @file_get_contents( $config );
        $entry  = '#("https://github\.com/ehb54/us3lims_dbinst\.git"\s*=>\s*")([^"]*)(")#';
        if ( $text === false || !preg_match( $entry, $text, $m ) ) {
            report( 'note', "$config has no dbinst entry in \$repo_branches; newinst clones dbinst's default branch" );
            return;
        }
        $refs = [];
        foreach ( glob( rtrim( $context[ 'wwwpath' ], '/' ) . '/uslims3/*/lib/dbinst_config_loader.php' ) ?: [] as $loader ) {
            $refs[ dbinst_checkout_ref( dirname( $loader, 2 ) ) ] = true;
        }
        $refs = array_keys( $refs );
        if ( count( $refs ) !== 1 || $refs[ 0 ] === '' ) {
            if ( !in_array( $m[ 2 ], $refs, true ) ) {
                report( 'note', "db_config.php's \$repo_branches names dbinst '{$m[2]}', and this host's instances don't run one"
                              . " branch or tag (" . ( $refs ? implode( ', ', array_map( function ( $r ) { return $r === '' ? '(none)' : $r; }, $refs ) ) : 'no instance' )
                              . "); newinst clones dbinst from it, so set it in $config to this release's" );
            }
            return;
        }
        $ref = $refs[ 0 ];
        if ( $m[ 2 ] === $ref ) {
            report( 'ok', "db_config.php's \$repo_branches names dbinst '$ref', as the instances run" );
            return;
        }
        report( 'todo', "set db_config.php's \$repo_branches dbinst entry from '{$m[2]}' to '$ref', which the instances run"
                      . " and newinst clones a new instance from" );
        if ( !$apply || !confirm( "Set the dbinst entry in $config to '$ref'?" ) ) {
            return;
        }
        write_file( $config, preg_replace( $entry, '${1}' . str_replace( [ '\\', '$' ], [ '\\\\', '\\$' ], $ref ) . '${3}', $text, 1 ) );
        report( 'done', "db_config.php's dbinst entry is '$ref'" );
    },
];
