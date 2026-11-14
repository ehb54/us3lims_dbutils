<?php
## Rewrites listen-config.php from gridctl's own template, carrying only
## this site's values forward -- the move off the pre-4.3.0 file shape
## (class_local/ removed, settings-only contract, $listen_config_version
## stamped in). A file rewrite, not database work: $db_handle/$db are
## unused, same as common_dir_backfill's instance-scope migration ignores
## them for the same reason.
##
## Native signal, no ledger needed: $listen_config_version >= 2 is read
## directly from the file this migration describes, and travels with that
## file on its own (a restored backup of just this file still carries the
## right answer) -- a separate ledger row would be a second, redundant
## source of truth for a question the file already answers authoritatively.
return [
    'id'            => 'listen_config_v2',
    'since_version' => '4.3.0',
    'scope'         => 'host',
    'description'   => 'Rewrites listen-config.php from the gridctl template (values only, version 2).',
    'run'           => function ( $db_handle, $db, $apply, $context ) {
        $listen_config = $context[ 'listen_config' ];
        $template      = $context[ 'template' ];
        $old_listen    = $context[ 'old_listen' ];

        if ( ( $old_listen[ 'listen_config_version' ] ?? 0 ) >= 2 ) {
            report( 'ok', "$listen_config is already version 2" );
            return;
        }
        if ( !is_file( $template ) ) {
            ## Later steps assume the new contract is in place, so this
            ## cannot be a warning the script walks past.
            fatal( "template not found: $template (update gridctl first)" );
        }

        ## Site values carried from the old file; everything else comes
        ## from the template.
        $carried = [ 'submit_dir', 'listen_port', 'dbhost', 'servhost', 'host_name', 'guser', 'gDB', 'user',
                     'org_name', 'org_domain', 'admin_email', 'logging_level' ];

        $text = file_get_contents( $template );
        if ( $text === false ) {
            fatal( "could not read the template $template" );
        }
        if ( !isset( $old_listen[ 'host_name' ] ) && isset( $old_listen[ 'servhost' ] ) ) {
            $old_listen[ 'host_name' ] = $old_listen[ 'servhost' ];
        }
        $carried_keys = [];
        foreach ( $carried as $key ) {
            if ( !array_key_exists( $key, $old_listen ) ) {
                continue;
            }
            $text = substitute( $text, $key, $old_listen[ $key ], $replaced );
            if ( $replaced ) {
                $carried_keys[] = $key;
            } else {
                ## The template has no such setting, so the old value has nowhere to go.
                report( 'note', "old setting \$$key has no place in the new template and will not be carried; review it" );
            }
        }

        $tz = null;
        ## The time zone is a call, not a variable, so config_vars() does not see it.
        if ( preg_match( '/^\s*date_default_timezone_set\(\s*([\'"])([^\'"]+)\1\s*\)/m',
                         (string) file_get_contents( $listen_config ), $tz ) ) {
            ## Callback form for the same reason substitute() uses one: an exported
            ## value in a replacement string would have $n and backslashes reread.
            $zone   = $tz[ 2 ];
            $result = preg_replace_callback( '/^(\s*date_default_timezone_set\(\s*)[\'"][^\'"]+[\'"]/m',
                function ( $m ) use ( $zone ) {
                    return $m[ 1 ] . var_export( $zone, true );
                }, $text, 1 );
            if ( $result === null ) {
                fatal( "could not carry the time zone: preg error " . preg_last_error() );
            }
            $text = $result;
        }

        ## class_local/ was removed with the Slurm change; the classes are in class/.
        $class_dir = $old_listen[ 'class_dir' ] ?? "{$context['wwwpath']}/common/class/";
        $class_dir = preg_replace( '~/class_local/?$~', '/class/', rtrim( $class_dir, '/' ) . '/' );
        if ( $class_dir === null ) {
            fatal( "could not normalize class_dir '" . ( $old_listen[ 'class_dir' ] ?? '' ) . "'" );
        }
        $text = substitute( $text, 'class_dir', $class_dir, $replaced );
        if ( !$replaced ) {
            fatal( "the template $template does not set \$class_dir" );
        }

        $template_vars = [];
        preg_match_all( '/^\$(\w+)\s*=/m', $text, $m );
        $template_vars = $m[ 1 ];
        ## Derived at run time by the template, or retired with class_local/.
        $runtime = [ 'home', 'home_remote', 'work', 'work_remote', 'pipe', 'logfile', 'lock_dir', 'cfgfile',
                     'configs', 'gpasswd', 'passwd', 'self', 'errors', 'db_handle', 'us3pwentry',
                     'class_dir_p', 'class_dir_d', 'class_dir_l' ];
        ## Settings the old file had that the new contract has no home for. Values
        ## already carried are excluded, so each one is reported once.
        $unreviewed = array_diff( array_keys( $old_listen ), $template_vars, $runtime, $carried_keys );

        report( 'todo', "rewrite from the template, carrying " . implode( ', ', $carried_keys )
                        . ( $tz ? ", time zone $tz[2]" : '' ) . "; class_dir $class_dir" );
        foreach ( $unreviewed as $key ) {
            report( 'note', "old setting \$$key is not in the new contract and will not be carried; review it" );
        }

        if ( $apply && confirm( "Rewrite $listen_config from the template?" ) ) {
            ## write_file lints the result (and restores the original if
            ## it is broken) and increments $changes itself -- do not also
            ## increment it here.
            write_file( $listen_config, $text );
            report( 'done', "$listen_config rewritten (original in " . backup_path( $listen_config ) . ")" );
        }
    },
];
