-- Esquema de base de datos para el sistema de ticketing
-- Creación de tablas

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre VARCHAR(30) NOT NULL,
    correo VARCHAR(100) UNIQUE NOT NULL,
    rol VARCHAR(15) NOT NULL CHECK (rol IN ('usuario', 'soporte', 'administrador')),
    contraseña VARCHAR(255) NOT NULL,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de tickets
CREATE TABLE IF NOT EXISTS tickets (
    id_ticket INTEGER PRIMARY KEY AUTOINCREMENT,
    titulo VARCHAR(100) NOT NULL,
    descripcion TEXT NOT NULL,
    categoria VARCHAR(50) NOT NULL,
    prioridad VARCHAR(10) NOT NULL CHECK (prioridad IN ('alta', 'media', 'baja')),
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado VARCHAR(15) NOT NULL CHECK (estado IN ('abierto', 'en proceso', 'cerrado')),
    id_usuario INTEGER NOT NULL,
    id_asignado INTEGER,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
    FOREIGN KEY (id_asignado) REFERENCES usuarios(id_usuario)
);

-- Tabla de acciones (historial de cambios en los tickets)
CREATE TABLE IF NOT EXISTS acciones (
    id_accion INTEGER PRIMARY KEY AUTOINCREMENT,
    id_ticket INTEGER NOT NULL,
    id_usuario INTEGER NOT NULL,
    descripcion TEXT NOT NULL,
    fecha_accion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_ticket) REFERENCES tickets(id_ticket),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);

-- Tabla para almacenar archivos adjuntos
CREATE TABLE IF NOT EXISTS archivos (
    id_archivo INTEGER PRIMARY KEY AUTOINCREMENT,
    id_ticket INTEGER NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    ruta_archivo VARCHAR(255) NOT NULL,
    tipo_archivo VARCHAR(100) NOT NULL,
    fecha_subida DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_ticket) REFERENCES tickets(id_ticket)
);

-- Tabla para almacenar la base de conocimientos
CREATE TABLE IF NOT EXISTS conocimientos (
    id_conocimiento INTEGER PRIMARY KEY AUTOINCREMENT,
    categoria VARCHAR(50) NOT NULL,
    titulo VARCHAR(100) NOT NULL,
    resumen TEXT NOT NULL,
    contenido TEXT NOT NULL,
    imagen VARCHAR(255),
    etiquetas TEXT,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    visitas INTEGER DEFAULT 0,
    id_autor INTEGER NOT NULL,
    FOREIGN KEY (id_autor) REFERENCES usuarios(id_usuario)
);

-- Tabla para almacenar valoraciones de artículos
CREATE TABLE IF NOT EXISTS valoraciones (
    id_valoracion INTEGER PRIMARY KEY AUTOINCREMENT,
    id_conocimiento INTEGER NOT NULL,
    id_usuario INTEGER NOT NULL,
    valoracion INTEGER NOT NULL CHECK (valoracion IN (1, 2, 3, 4, 5)),
    comentario TEXT,
    fecha_valoracion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_conocimiento) REFERENCES conocimientos(id_conocimiento),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
    UNIQUE(id_conocimiento, id_usuario)
);

-- Tabla para registrar artículos relacionados
CREATE TABLE IF NOT EXISTS articulos_relacionados (
    id_relacion INTEGER PRIMARY KEY AUTOINCREMENT,
    id_articulo INTEGER NOT NULL,
    id_articulo_relacionado INTEGER NOT NULL,
    FOREIGN KEY (id_articulo) REFERENCES conocimientos(id_conocimiento),
    FOREIGN KEY (id_articulo_relacionado) REFERENCES conocimientos(id_conocimiento),
    UNIQUE(id_articulo, id_articulo_relacionado)
);

-- Índices para mejorar el rendimiento
CREATE INDEX idx_tickets_usuario ON tickets(id_usuario);
CREATE INDEX idx_tickets_estado ON tickets(estado);
CREATE INDEX idx_acciones_ticket ON acciones(id_ticket);
CREATE INDEX idx_archivos_ticket ON archivos(id_ticket);
CREATE INDEX idx_conocimientos_categoria ON conocimientos(categoria);
CREATE INDEX idx_conocimientos_etiquetas ON conocimientos(etiquetas);
CREATE INDEX idx_valoraciones_conocimiento ON valoraciones(id_conocimiento);
