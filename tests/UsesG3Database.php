<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Support\Facades\DB;

trait UsesG3Database
{
    public function beginDatabaseTransaction(): void
    {
        $canConnect = false;
        try {
            $pdo = @new \PDO('mysql:host=127.0.0.1;port=3306;dbname=distribuidorapyb', 'root', '', [
                \PDO::ATTR_TIMEOUT => 1,
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);
            $canConnect = true;
        } catch (\Throwable $e) {
            $canConnect = false;
        }

        if (! $canConnect) {
            $this->markTestSkipped('Requiere MySQL activo en 127.0.0.1:3306 con base distribuidorapyb.');
            return;
        }

        config([
            'database.default' => 'mysql',
            'database.connections.mysql.host' => '127.0.0.1',
            'database.connections.mysql.port' => '3306',
            'database.connections.mysql.database' => 'distribuidorapyb',
            'database.connections.mysql.username' => 'root',
            'database.connections.mysql.password' => '',
        ]);
        DB::purge('mysql');
        DB::setDefaultConnection('mysql');

        $database = $this->app->make('db');

        foreach ($this->connectionsToTransact() as $name) {
            $connection = $database->connection($name);
            $dispatcher = $connection->getEventDispatcher();

            $connection->unsetEventDispatcher();
            $connection->beginTransaction();
            $connection->setEventDispatcher($dispatcher);
        }

        $this->beforeApplicationDestroyed(function () use ($database) {
            foreach ($this->connectionsToTransact() as $name) {
                $connection = $database->connection($name);
                $dispatcher = $connection->getEventDispatcher();

                $connection->unsetEventDispatcher();
                $connection->rollBack();
                $connection->setEventDispatcher($dispatcher);
                $connection->disconnect();
            }
        });
    }
}
