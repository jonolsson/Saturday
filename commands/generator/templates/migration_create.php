<?php
class create_name extends commands_migration_migrations {
    function up() {
        $this->create_table (
            'name',
            array(
                fields
            ),
            'id'
        );
    }

    function down() {
        $this->drop_table('name');
    }
}
