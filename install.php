<?php 
// Install database table for API keys
rex_sql_table::get(rex::getTable('erecht24'))
    ->ensurePrimaryIdColumn()
    ->ensureColumn(new rex_sql_column('domain', 'varchar(191)'))
    ->ensureIndex(new rex_sql_index('domain', ['domain'], rex_sql_index::UNIQUE))
    ->ensureColumn(new rex_sql_column('api_key', 'varchar(191)'))
    ->ensureColumn(new rex_sql_column('secret', 'varchar(191)'))
    ->ensureColumn(new rex_sql_column('client_id', 'varchar(191)'))
    ->ensureColumn(new rex_sql_column('updatedate', 'datetime'))
    ->ensureColumn(new rex_sql_column('createdate', 'datetime'))
    ->ensure();

// Install database table for texts
rex_sql_table::get(rex::getTable('erecht24_texts'))
    ->ensurePrimaryIdColumn()
    ->ensureColumn(new rex_sql_column('domain', 'varchar(191)'))
    ->ensureColumn(new rex_sql_column('type', 'varchar(32)'))  // imprint, privacy_policy, privacy_policy_social_media
    ->ensureIndex(new rex_sql_index('domain_type', ['domain', 'type'], rex_sql_index::UNIQUE))  // Kombinierter Index
    ->ensureColumn(new rex_sql_column('html_de', 'text'))
    ->ensureColumn(new rex_sql_column('html_en', 'text'))
    ->ensureColumn(new rex_sql_column('last_fetch', 'datetime')) // Neues Feld für letzten Abruf
    ->ensureColumn(new rex_sql_column('updatedate', 'datetime'))
    ->ensureColumn(new rex_sql_column('createdate', 'datetime'))
    ->ensure();
