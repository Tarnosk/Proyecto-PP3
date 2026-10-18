USE sistema_gestion;

ALTER TABLE usuarios
    MODIFY rol ENUM('administrativo', 'vendedor', 'repartidor', 'contador') NOT NULL;

UPDATE usuarios
SET usuario = 'repartidor',
    password_hash = '$2y$10$1w7KtO7GmkddmHApsSLMt.GnObzeT55v1pw6P4cYttd3IkjiWcOIW',
    nombre = 'Usuario Repartidor',
    rol = 'repartidor',
    estado = 'activo'
WHERE usuario = 'vendedor';

UPDATE usuarios
SET rol = 'repartidor'
WHERE rol = 'vendedor';

INSERT INTO usuarios (usuario, password_hash, nombre, rol, estado)
VALUES ('contador', '$2y$10$sLTqTWYXWdUXeBapcdmP1OmUwlCtPy.S9gb8Icfc22HgmJSHhZF8m', 'Usuario Contador', 'contador', 'activo')
ON DUPLICATE KEY UPDATE
    password_hash = VALUES(password_hash),
    nombre = VALUES(nombre),
    rol = VALUES(rol),
    estado = VALUES(estado);

ALTER TABLE usuarios
    MODIFY rol ENUM('administrativo', 'repartidor', 'contador') NOT NULL;
