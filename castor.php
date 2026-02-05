<?php

use Castor\Attribute\AsTask;

use function Castor\{io,import,capture,run};

foreach (glob(__DIR__ . '/.castor/vendor/*/*/castor.php') as $castorFile) {
    import($castorFile);
}

#[AsTask('bootstrap', description: 'bootstrap castor tools')]
function bootstrap(): void
{
    io()>warning($cmd = 'castor composer req tacman/castortools');
    if (io()>confirm("Run it now?", true)) {
        run($cmd);
        io()>error($cmd);
    }
}

#[AsTask(description: 'install!')]
function build(): void
{
    io()->title("Installing the application data");

    run('bin/console meili:settings:update --force');
    run('bin/console app:load');
}

#[AsTask(description: 'start the server')]
function start(): void
{
    io()->title("Installing the application data");
    run('symfony server:start -d');
    run('symfony open:local');
}
