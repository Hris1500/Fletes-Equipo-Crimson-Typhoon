<?php
$db = new SQLite3('database.sqlite');
$db->exec('PRAGMA foreign_keys = ON;');

$db->exec("
CREATE TABLE IF NOT EXISTS choferes (
  id_chofer    INTEGER PRIMARY KEY AUTOINCREMENT,
  nombre       TEXT NOT NULL,
  telefono     TEXT,
  num_licencia TEXT NOT NULL UNIQUE,
  activo       INTEGER DEFAULT 1
);

CREATE TABLE IF NOT EXISTS unidades (
  id_unidad    INTEGER PRIMARY KEY AUTOINCREMENT,
  placas       TEXT NOT NULL UNIQUE,
  tipo         TEXT,
  capacidad_kg REAL,
  activa       INTEGER DEFAULT 1
);

CREATE TABLE IF NOT EXISTS clientes (
  id_cliente   INTEGER PRIMARY KEY AUTOINCREMENT,
  nombre       TEXT NOT NULL,
  telefono     TEXT,
  correo       TEXT,
  direccion    TEXT
);

CREATE TABLE IF NOT EXISTS fletes (
  id_flete          INTEGER PRIMARY KEY AUTOINCREMENT,
  id_cliente        INTEGER NOT NULL,
  id_chofer         INTEGER NOT NULL,
  id_unidad         INTEGER NOT NULL,
  origen            TEXT NOT NULL,
  destino           TEXT NOT NULL,
  descripcion_carga TEXT,
  fecha_salida      TEXT,
  fecha_entrega     TEXT,
  monto             REAL NOT NULL,
  estatus           TEXT DEFAULT 'pendiente'
                    CHECK (estatus IN ('pendiente','en_transito','finalizado')),
  pagado            INTEGER DEFAULT 0,
  FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente),
  FOREIGN KEY (id_chofer)  REFERENCES choferes(id_chofer),
  FOREIGN KEY (id_unidad)  REFERENCES unidades(id_unidad)
);
");

/* Datos de ejemplo (seed) */
$db->exec("
INSERT INTO choferes (nombre, telefono, num_licencia) VALUES
 ('Juan Pérez', '4621112233', 'LIC-001'),
 ('Luis Ramírez', '4624445566', 'LIC-002');

INSERT INTO unidades (placas, tipo, capacidad_kg) VALUES
 ('GTO-123-A', 'Torton', 10000),
 ('GTO-456-B', 'Tráiler', 25000);

INSERT INTO clientes (nombre, telefono, correo, direccion) VALUES
 ('Abarrotes del Bajío', '4627778899', 'contacto@abarrotes.mx', 'Irapuato, Gto.'),
 ('Ferretería Central', '4620001122', 'ventas@ferreteria.mx', 'León, Gto.');

INSERT INTO fletes (id_cliente, id_chofer, id_unidad, origen, destino,
                    descripcion_carga, fecha_salida, monto, estatus, pagado) VALUES
 (1, 1, 1, 'Irapuato', 'Guadalajara', 'Abarrotes', '2026-10-10', 8500, 'en_transito', 0),
 (2, 2, 2, 'León', 'CDMX', 'Material de construcción', '2026-10-05', 15000, 'finalizado', 1),
 (1, 2, 2, 'Irapuato', 'Querétaro', 'Bebidas', '2026-10-14', 6000, 'pendiente', 0);
");

echo "Base de datos creada correctamente.";