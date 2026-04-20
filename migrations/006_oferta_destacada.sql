-- Mapita N5: Oferta destacada por negocio (aditivo, convive con pins de ofertas)
ALTER TABLE businesses
    ADD COLUMN IF NOT EXISTS oferta_activa_id INT UNSIGNED NULL,
    ADD KEY idx_business_oferta_activa (oferta_activa_id);

ALTER TABLE ofertas
    ADD COLUMN IF NOT EXISTS es_destacada TINYINT(1) NOT NULL DEFAULT 0,
    ADD KEY idx_ofertas_destacada (business_id, activo, es_destacada);
