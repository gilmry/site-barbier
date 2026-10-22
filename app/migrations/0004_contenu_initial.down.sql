-- Retire exactement le contenu initial (les contenus ajoutés ensuite dans l'admin restent).
DELETE FROM translation WHERE (owner_type = 'section' AND owner_id IN ('hero', 'maison', 'carte', 'equipe', 'reservation', 'horaires', 'footer', 'legal_notice', 'privacy')) OR (owner_type = 'salon' AND owner_id = '1') OR owner_id LIKE 'c0000000-0000-4000-8000-%' OR owner_id LIKE '50000000-0000-4000-8000-%' OR owner_id LIKE 'b0000000-0000-4000-8000-%';
DELETE FROM barber_service WHERE barber_id LIKE 'b0000000-0000-4000-8000-%';
DELETE FROM barber WHERE id LIKE 'b0000000-0000-4000-8000-%';
DELETE FROM service WHERE id LIKE '50000000-0000-4000-8000-%';
DELETE FROM category WHERE id LIKE 'c0000000-0000-4000-8000-%';
DELETE FROM opening_hours WHERE weekday BETWEEN 1 AND 7;
DELETE FROM section WHERE section_key IN ('hero', 'maison', 'carte', 'equipe', 'reservation', 'horaires', 'footer', 'legal_notice', 'privacy');
DELETE FROM salon WHERE id = 1;
