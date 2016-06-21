<?php
/*
 * wula cli command. use this cli command to create project, install module and so on ....
 */
function recurse_copy($src, $dst) {
    if (! file_exists ( $src )) {
        return;
    }
    $dir = @opendir ( $src );
    
    if (! file_exists ( $dst )) {
        @mkdir ( $dst );
    }
    while ( false !== ($file = readdir ( $dir )) ) {
        if (($file != '.') && ($file != '..')) {
            if (is_dir ( $src . '/' . $file )) {
                recurse_copy ( $src . '/' . $file, $dst . '/' . $file );
            } else {
                copy ( $src . '/' . $file, $dst . '/' . $file );
            }
        }
    }
    @closedir ( $dir );
}
$options = getopt ( 'p', array (
    'init::'
) );
if (isset ( $options ['init'] )) {
    recurse_copy ( './demo', '.' );
}