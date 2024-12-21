<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class UseUUIDPrimaryKey extends AbstractMigration
{
    public function change(): void
    {
        // Enable pgcrypto extension to use gen_random_uuid()
        $this->execute("CREATE EXTENSION IF NOT EXISTS pgcrypto;");

        // Create a new "tasks" table with UUID as the primary key
        $table = $this->table('tasks', ['id' => false, 'primary_key' => ['id']]);

        // Add a UUID column (without default value for now)
        $table->addColumn('id', 'uuid', ['null' => false])
              ->addColumn('description', 'text', ['null' => false])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => false])
              ->create();

        // Set the default value for UUID column using raw SQL
        $this->execute("ALTER TABLE tasks ALTER COLUMN id SET DEFAULT gen_random_uuid();");
    }
}