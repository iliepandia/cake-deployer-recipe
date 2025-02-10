<?php
namespace Deployer;

require 'recipe/common.php';

set('keep_releases', 2);

// Project name
set('application', 'business_mon');

// Project repository
set('repository', 'git@github.com:iliepandia/business-monitor.git');

// [Optional] Allocate tty for git clone. Default value is false.
set('git_tty', true);

// Shared files/dirs between deploys
set('shared_files', []);
set('shared_dirs', [
    'tmp',
    'data',
    'logs',
]);


// Writable dirs by web server
set('writable_dirs', []);
set('allow_anonymous_stats', false);

// Hosts
localhost()
    ->set('deploy_path', '~/path/to/deployment/location/{{application}}');


// Tasks

desc('Deploy your project');
task('deploy', [
    'deploy:info',
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:writable',
    'deploy:vendors',
    'deploy:clear_paths',
    'deploy:symlink',
    'deploy:unlock',
    'cleanup',
    'success'
]);

// [Optional] If deploy fails automatically unlock.
after('deploy:failed', 'deploy:unlock');

task('compile_less', function(){
    writeln('{{release_path}}');
    cd( '{{release_path}}/webroot/css' );
    $result = run('ls -1 | grep ".less"');
    $result = explode( "\n", $result);
    foreach($result as $file){
        $baseName = substr($file, 0, strlen($file)-5);
        writeln( 'Compiling ' . $file . " to $baseName.css...");
        run("plessc $file > $baseName.css");
    }

});

task('update_revision_data',function(){
    writeln('{{release_path}}');
    cd( '{{release_path}}/config' );
    run( 'git log --pretty=format:"%h" -1 > .git-commit' );
    run( 'TZ=Europe/Bucharest date "+%Y-%m-%d %H:%M:%S" > .last-update' );
});

task('set_permissions',function(){
    cd( '{{release_path}}' );
    run( 'chmod +x bin/cake ' );
});

task('run_composer',function(){
    cd( '{{release_path}}' );
    run( '/usr/local/bin/php74 ~/www/monitor.ascension101.com/git_deploy/composer.phar install' );
});

task('clear_cache', function(){
    cd( '{{release_path}}' );
    run('bin/cake orm_cache clear');
    run('bin/cake orm_cache build');
});

// After the code has been updated run the less compiler
after('deploy:update_code', 'run_composer');
after('deploy:update_code', 'set_permissions');
after('deploy:symlink', 'clear_cache');
after('deploy:update_code', 'compile_less');
after('deploy:update_code', 'update_revision_data');
