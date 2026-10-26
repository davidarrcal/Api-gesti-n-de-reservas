CREATE DATABASE IF NOT EXISTS gestion_reservas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gestion_reservas;

DROP TABLE IF EXISTS reserva_evento;
DROP TABLE IF EXISTS reserva;

CREATE TABLE reserva (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre_huesped VARCHAR(120) NOT NULL,
    email_huesped VARCHAR(180) NOT NULL,
    nombre_alojamiento VARCHAR(160) NOT NULL,
    fecha_entrada DATE NOT NULL,
    fecha_salida DATE NOT NULL,
    estado ENUM('pendiente', 'confirmada', 'cancelada') NOT NULL DEFAULT 'pendiente',
    importe DECIMAL(10, 2) NOT NULL,
    notas TEXT NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_fechas_reserva CHECK (fecha_salida > fecha_entrada),
    CONSTRAINT chk_importe_reserva CHECK (importe >= 0),
    INDEX idx_reserva_estado (estado),
    INDEX idx_reserva_entrada (fecha_entrada),
    INDEX idx_reserva_huesped (nombre_huesped)
) ENGINE=InnoDB;

CREATE TABLE reserva_evento (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reserva_id INT UNSIGNED NOT NULL,
    tipo VARCHAR(40) NOT NULL,
    descripcion VARCHAR(255) NOT NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_evento_reserva FOREIGN KEY (reserva_id) REFERENCES reserva(id) ON DELETE CASCADE,
    INDEX idx_evento_reserva_fecha (reserva_id, fecha_creacion)
) ENGINE=InnoDB;

INSERT INTO reserva
    (nombre_huesped, email_huesped, nombre_alojamiento, fecha_entrada, fecha_salida, estado, importe, notas, fecha_creacion)
VALUES
    ('Lucia Martin', 'lucia.martin@example.com', 'Hotel Costa Azul', '2026-06-12', '2026-06-16', 'confirmada', 640.00, 'Habitacion doble y cuna.', '2026-04-02 10:15:00'),
    ('Carlos Perez', 'carlos.perez@example.com', 'Apartamentos La Plaza', '2026-07-03', '2026-07-08', 'pendiente', 875.50, 'Llegada despues de las 22:00.', '2026-04-12 09:30:00'),
    ('Ana Garcia', 'ana.garcia@example.com', 'Casa Rural El Olivo', '2026-05-20', '2026-05-23', 'cancelada', 420.00, 'Cancelada por el cliente.', '2026-03-18 16:45:00'),
    ('Miguel Santos', 'miguel.santos@example.com', 'Hotel Costa Azul', '2026-08-14', '2026-08-21', 'confirmada', 1120.00, NULL, '2026-04-20 12:05:00');

INSERT INTO reserva_evento (reserva_id, tipo, descripcion, fecha_creacion) VALUES
    (1, 'creacion', 'Reserva creada', '2026-04-02 10:15:00'),
    (1, 'cambio_estado', 'Reserva confirmada', '2026-04-02 10:20:00'),
    (2, 'creacion', 'Reserva creada', '2026-04-12 09:30:00'),
    (3, 'creacion', 'Reserva creada', '2026-03-18 16:45:00'),
    (3, 'cambio_estado', 'Reserva cancelada', '2026-03-19 08:10:00'),
    (4, 'creacion', 'Reserva creada', '2026-04-20 12:05:00'),
    (4, 'cambio_estado', 'Reserva confirmada', '2026-04-20 12:10:00');
