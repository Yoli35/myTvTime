<?php
namespace Deployer;

require 'recipe/symfony.php';

// Config

set('repository', 'https://github.com/Yoli35/myTvTime.git');
set('use_relative_symlink', false);

add('shared_files', []);
add('shared_dirs', []);
add('writable_dirs', []);

// Hosts

host('access922206626.webspace-data.io')
    ->set('remote_user', 'u109355875')
    ->set('deploy_path', '~/sites/mytvtime');

// Hooks

after('deploy:failed', 'deploy:unlock');
