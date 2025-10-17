CREATE DATABASE IF NOT EXISTS appmodel;
USE appmodel;

CREATE TABLE IF NOT EXISTS tipos_usuario (
    ID_TIP INT AUTO_INCREMENT PRIMARY KEY,
    nombre_tipo VARCHAR(20) NOT NULL UNIQUE
);

INSERT INTO tipos_usuario (nombre_tipo) VALUES
('ADMIN'),
('MODERADOR'),
('COMPRADOR'),
('VENDEDOR');

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_usuario VARCHAR(30) NOT NULL UNIQUE,
    nombre VARCHAR(50) NOT NULL,
    apellido VARCHAR(50) NOT NULL,
    correo VARCHAR(100) NOT NULL,
    fecha_nacimiento DATE NOT NULL,
    celular VARCHAR(10) NOT NULL,
    contrasena VARCHAR(100) NOT NULL,
    id_tipo_usuario INT NOT NULL,
    FOREIGN KEY (id_tipo_usuario) REFERENCES tipos_usuario(ID_TIP)
);

CREATE TABLE IF NOT EXISTS PRODUCTOS (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    NOM_PRO VARCHAR(50) NOT NULL,
    DES_PRO VARCHAR(255) NOT NULL,
    PRECIO NUMBER(5, 2) NOT NULL
);

INSERT INTO usuarios (nombre_usuario, nombre, apellido, correo, fecha_nacimiento, celular, contrasena, id_tipo_usuario)
VALUES 
('sebas_vaca', 'SEBASTIAN', 'VACA', 'sebas_cando2000@hotmail.com', '2000-12-20', '0978930149', 'T001', 3),
('admin', 'Admin', 'Principal', 'admin1234@gmail.com', '1990-01-01', '0999999999', 'ADMIN12345', 1);
