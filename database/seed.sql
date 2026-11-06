-- Datos opcionales de ejemplo. Seguro de ejecutar más de una vez.
INSERT INTO salones (nombre,ubicacion,capacidad,descripcion) VALUES
('Salón 1','Primer nivel',30,'Salón institucional'),
('Salón 2','Segundo nivel',20,'Salón de reuniones'),
('Salón 3','Tercer nivel',15,'Sala de coordinación')
ON CONFLICT (nombre) DO NOTHING;

INSERT INTO equipos (nombre,cantidad_total,descripcion) VALUES
('Sillas',100,'Sillas para reuniones'),
('Mesas',20,'Mesas plegables'),
('Cañonera',2,'Proyector multimedia'),
('Equipo de sonido',2,'Equipo de audio'),
('Cafetera',3,'Cafetera institucional')
ON CONFLICT (nombre) DO NOTHING;
