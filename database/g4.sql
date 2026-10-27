CREATE DATABASE pps3proyecto;

CREATE TABLE VENTA (
id_venta int primary key auto_increment,
id_cliente int NOT NULL,
fecha date NOT NULL,
descuento_global decimal,
total decimal NOT NULL,
numFactura varchar(50) NOT NULL,
estado enum ('pendiente','confirmada','pagada','facturada', 'cancelada') NOT NULL,
observaciones varchar(150) NOT NULL,
id_usuario int NOT NULL);

CREATE TABLE PROMOCION (
id_promocion int primary key auto_increment,
nombre varchar(50) NOT NULL,
total decimal NOT NULL,
vigencia_desde date NOT NULL,
vigencia_hasta date NOT NULL,
descripcion varchar(150),
estado boolean);

CREATE TABLE DETALLE_VENTA (
id_detalle int primary key auto_increment,
id_venta int NOT NULL,
id_producto int NOT NULL,
id_promocion int,
cantidad int NOT NULL,
precio_unitario decimal NOT NULL,
descuento DECIMAL(10,2),
subtotal decimal NOT NULL,
foreign key (id_venta) references VENTA(id_venta),
foreign key (id_promocion) references PROMOCION(id_promocion));

CREATE TABLE PROMOCION_PRODUCTO (
id_promocion int NOT NULL,
id_producto int NOT NULL,
cantidad int,
foreign key (id_promocion) references PROMOCION(id_promocion));


CREATE TABLE cuenta_corriente_movimientos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT UNSIGNED NOT NULL,
    venta_id BIGINT UNSIGNED NULL,
    tipo VARCHAR(20) NOT NULL,
    descripcion VARCHAR(255) NOT NULL,
    importe DECIMAL(12,2) NOT NULL,
    referencia_externa VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_cliente_created_at (cliente_id, created_at)
);
