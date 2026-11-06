<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/src/Config.php';
require dirname(__DIR__) . '/src/Logger.php';
require dirname(__DIR__) . '/src/Database.php';
\App\Config::load(dirname(__DIR__));
try {
    $pdo=\App\Database::connection();
    $deleted=$pdo->exec("DELETE FROM tokens_revocados WHERE expira_en < CURRENT_TIMESTAMP - INTERVAL '7 days'");
    \App\Logger::write('info','Cron de mantenimiento completado',['tokens_revocados_eliminados'=>$deleted]);
    echo date(DATE_ATOM)." OK tokens_revocados_eliminados={$deleted}\n";
} catch (Throwable $e) {
    \App\Logger::write('error','Cron de mantenimiento falló',['error'=>$e->getMessage(),'type'=>get_class($e)]);
    fwrite(STDERR,date(DATE_ATOM)." ERROR: tarea fallida. Revisa logs.\n");
    exit(1);
}
