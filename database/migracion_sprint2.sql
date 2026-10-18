USE sistema_gestion;

ALTER TABLE usuarios
    ADD COLUMN current_session_id VARCHAR(128) NULL AFTER ultimo_intento_fallido;

CREATE TABLE IF NOT EXISTS reclamos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    asunto VARCHAR(160) NOT NULL,
    descripcion TEXT NOT NULL,
    prioridad ENUM('baja', 'media', 'alta') NOT NULL DEFAULT 'media',
    estado ENUM('abierto', 'en_proceso', 'cerrado') NOT NULL DEFAULT 'abierto',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_reclamos_cliente_id (cliente_id),
    KEY idx_reclamos_usuario_id (usuario_id),
    KEY idx_reclamos_estado (estado),
    CONSTRAINT fk_reclamos_cliente_id
        FOREIGN KEY (cliente_id) REFERENCES clientes(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_reclamos_usuario_id
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
