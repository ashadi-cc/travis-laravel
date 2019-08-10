@servers(['web' => 'deployer@157.230.83.59'])

@setup
    $repository = 'git@gitlab.com:ashadi-cc/ci-laravel.git';
    $releases_dir = '/var/www/deploy/releases';
    $app_dir = '/var/www/ci-laravel';
    $release = date('YmdHis');
    $new_release_dir = $releases_dir .'/'. $release;
@endsetup

@story('deploy')
    clone_repository
    run_composer
    update_symlinks
@endstory

@task('clone_repository')
    echo 'Cloning repository'
    [ -d {{ $releases_dir }} ] || mkdir {{ $releases_dir }}
    git clone --depth 1 {{ $repository }} {{ $new_release_dir }}
    cd {{ $new_release_dir }}
    git reset --hard {{ $commit }}
@endtask

@task('run_composer')
    echo "Starting deployment ({{ $release }})"
    cd {{ $new_release_dir }}
    composer install --prefer-dist --no-scripts -q -o
@endtask

@task('update_symlinks')
    echo "Linking storage directory"
    rm -rf {{ $new_release_dir }}/storage
    ln -nfs {{ $app_dir }}/storage {{ $new_release_dir }}/storage

    echo 'Linking .env file'
    ln -nfs {{ $app_dir }}/.env {{ $new_release_dir }}/.env

    echo 'Linking current release'
    ln -nfs {{ $new_release_dir }} {{ $app_dir }}/current
@endtask

@story('deploy_staging')
    clone_staging
@endstory

@task('clone_staging', ['on' => 'web'])
    echo 'Cloning repository'
    [ -d {{ $app_dir }} ] || mkdir {{ $app_dir }} & git clone --depth 1 {{ $repository }} {{ $app_dir }}
    [ -f {{ $app_dir }}/.env ] || cp {{ $app_dir }}/.env.exampe {{ $app_dir }}/.env
    cd {{ $app_dir }}
    git checkout develop
    git reset --hard origin/develop
    git pull origin develop
    composer install
    php artisan migrate
@endtask