USE sistema_gestion;

ALTER TABLE usuarios
    ADD COLUMN intentos_fallidos TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER estado,
    ADD COLUMN bloqueado_hasta DATETIME NULL AFTER intentos_fallidos,
    ADD COLUMN ultimo_intento_fallido DATETIME NULL AFTER bloqueado_hasta;
