-- Ejecutar una sola vez en Neon para garantizar el catálogo de referencia solicitado.
-- Si ya existen equipos con estos nombres, no los duplica.
INSERT INTO equipos (nombre, cantidad_total, descripcion, activo) VALUES
  ('Cafetera', 1, 'Cafetera para reuniones institucionales', TRUE),
  ('Cañonera', 1, 'Proyector para presentaciones', TRUE),
  ('Sonido', 1, 'Equipo de sonido y audio', TRUE),
  ('Manteles', 1, 'Manteles para mesas de reunión', TRUE)
ON CONFLICT (nombre) DO NOTHING;
