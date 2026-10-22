-- Contenu initial : maquette « Le Corbeau » (FR) et ses traductions NL/EN. Tout est modifiable dans l'admin.
-- Identifiants fixes (préfixes c0…, 50…, b0…) : la réversion supprime exactement ces lignes.
INSERT INTO salon (id, name, phone, email) VALUES (1, 'Le Corbeau', '+32 2 000 00 00', 'contact@lecorbeau.be');
INSERT INTO section (section_key, page, position, version) VALUES ('hero', 'home', 1, 1);
INSERT INTO section (section_key, page, position, version) VALUES ('maison', 'home', 2, 1);
INSERT INTO section (section_key, page, position, version) VALUES ('carte', 'home', 3, 1);
INSERT INTO section (section_key, page, position, version) VALUES ('equipe', 'home', 4, 1);
INSERT INTO section (section_key, page, position, version) VALUES ('reservation', 'home', 5, 1);
INSERT INTO section (section_key, page, position, version) VALUES ('horaires', 'home', 6, 1);
INSERT INTO section (section_key, page, position, version) VALUES ('footer', 'home', 7, 1);
INSERT INTO section (section_key, page, position, version) VALUES ('legal_notice', 'legal', 1, 1);
INSERT INTO section (section_key, page, position, version) VALUES ('privacy', 'legal', 2, 1);
INSERT INTO category (id, position) VALUES ('c0000000-0000-4000-8000-000000000001', 1);
INSERT INTO category (id, position) VALUES ('c0000000-0000-4000-8000-000000000002', 2);
INSERT INTO service (id, category_id, price_cents, duration_min, published, position) VALUES ('50000000-0000-4000-8000-000000000001', 'c0000000-0000-4000-8000-000000000001', 2800, 45, 1, 1);
INSERT INTO service (id, category_id, price_cents, duration_min, published, position) VALUES ('50000000-0000-4000-8000-000000000002', 'c0000000-0000-4000-8000-000000000001', 3400, 60, 1, 2);
INSERT INTO service (id, category_id, price_cents, duration_min, published, position) VALUES ('50000000-0000-4000-8000-000000000003', 'c0000000-0000-4000-8000-000000000001', 3200, 45, 1, 3);
INSERT INTO service (id, category_id, price_cents, duration_min, published, position) VALUES ('50000000-0000-4000-8000-000000000004', 'c0000000-0000-4000-8000-000000000001', 1800, 30, 1, 4);
INSERT INTO service (id, category_id, price_cents, duration_min, published, position) VALUES ('50000000-0000-4000-8000-000000000005', 'c0000000-0000-4000-8000-000000000002', 2200, 30, 1, 1);
INSERT INTO service (id, category_id, price_cents, duration_min, published, position) VALUES ('50000000-0000-4000-8000-000000000006', 'c0000000-0000-4000-8000-000000000002', 3500, 40, 1, 2);
INSERT INTO service (id, category_id, price_cents, duration_min, published, position) VALUES ('50000000-0000-4000-8000-000000000007', 'c0000000-0000-4000-8000-000000000002', 4500, 75, 1, 3);
INSERT INTO service (id, category_id, price_cents, duration_min, published, position) VALUES ('50000000-0000-4000-8000-000000000008', 'c0000000-0000-4000-8000-000000000002', 6500, 100, 1, 4);
INSERT INTO barber (id, first_name, active, position) VALUES ('b0000000-0000-4000-8000-000000000001', 'Antoine', 1, 1);
INSERT INTO barber_service (barber_id, service_id) VALUES ('b0000000-0000-4000-8000-000000000001', '50000000-0000-4000-8000-000000000001');
INSERT INTO barber_service (barber_id, service_id) VALUES ('b0000000-0000-4000-8000-000000000001', '50000000-0000-4000-8000-000000000002');
INSERT INTO barber_service (barber_id, service_id) VALUES ('b0000000-0000-4000-8000-000000000001', '50000000-0000-4000-8000-000000000003');
INSERT INTO barber_service (barber_id, service_id) VALUES ('b0000000-0000-4000-8000-000000000001', '50000000-0000-4000-8000-000000000004');
INSERT INTO barber_service (barber_id, service_id) VALUES ('b0000000-0000-4000-8000-000000000001', '50000000-0000-4000-8000-000000000005');
INSERT INTO barber_service (barber_id, service_id) VALUES ('b0000000-0000-4000-8000-000000000001', '50000000-0000-4000-8000-000000000006');
INSERT INTO barber_service (barber_id, service_id) VALUES ('b0000000-0000-4000-8000-000000000001', '50000000-0000-4000-8000-000000000007');
INSERT INTO barber_service (barber_id, service_id) VALUES ('b0000000-0000-4000-8000-000000000001', '50000000-0000-4000-8000-000000000008');
INSERT INTO barber (id, first_name, active, position) VALUES ('b0000000-0000-4000-8000-000000000002', 'Samir', 1, 2);
INSERT INTO barber_service (barber_id, service_id) VALUES ('b0000000-0000-4000-8000-000000000002', '50000000-0000-4000-8000-000000000001');
INSERT INTO barber_service (barber_id, service_id) VALUES ('b0000000-0000-4000-8000-000000000002', '50000000-0000-4000-8000-000000000002');
INSERT INTO barber_service (barber_id, service_id) VALUES ('b0000000-0000-4000-8000-000000000002', '50000000-0000-4000-8000-000000000004');
INSERT INTO barber_service (barber_id, service_id) VALUES ('b0000000-0000-4000-8000-000000000002', '50000000-0000-4000-8000-000000000007');
INSERT INTO barber_service (barber_id, service_id) VALUES ('b0000000-0000-4000-8000-000000000002', '50000000-0000-4000-8000-000000000008');
INSERT INTO barber (id, first_name, active, position) VALUES ('b0000000-0000-4000-8000-000000000003', 'Léa', 1, 3);
INSERT INTO barber_service (barber_id, service_id) VALUES ('b0000000-0000-4000-8000-000000000003', '50000000-0000-4000-8000-000000000001');
INSERT INTO barber_service (barber_id, service_id) VALUES ('b0000000-0000-4000-8000-000000000003', '50000000-0000-4000-8000-000000000003');
INSERT INTO barber_service (barber_id, service_id) VALUES ('b0000000-0000-4000-8000-000000000003', '50000000-0000-4000-8000-000000000005');
INSERT INTO barber_service (barber_id, service_id) VALUES ('b0000000-0000-4000-8000-000000000003', '50000000-0000-4000-8000-000000000006');
INSERT INTO barber_service (barber_id, service_id) VALUES ('b0000000-0000-4000-8000-000000000003', '50000000-0000-4000-8000-000000000007');
INSERT INTO opening_hours (weekday, opens, closes) VALUES (2, '10:00:00', '19:00:00');
INSERT INTO opening_hours (weekday, opens, closes) VALUES (3, '10:00:00', '19:00:00');
INSERT INTO opening_hours (weekday, opens, closes) VALUES (4, '10:00:00', '21:00:00');
INSERT INTO opening_hours (weekday, opens, closes) VALUES (5, '10:00:00', '21:00:00');
INSERT INTO opening_hours (weekday, opens, closes) VALUES (6, '09:00:00', '17:00:00');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'hero', 'eyebrow', 'fr', 'Barbier · Bruxelles · Sur rendez-vous');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'hero', 'eyebrow', 'nl', 'Barbier · Brussel · Op afspraak');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'hero', 'eyebrow', 'en', 'Barber · Brussels · By appointment');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'hero', 'title', 'fr', 'Le Corbeau');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'hero', 'title', 'nl', 'Le Corbeau');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'hero', 'title', 'en', 'Le Corbeau');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'hero', 'lead', 'fr', 'Coupe aux ciseaux, taille de barbe et rasage à la lame.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'hero', 'lead', 'nl', 'Knippen met de schaar, baard trimmen en scheren met het mes.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'hero', 'lead', 'en', 'Scissor cuts, beard trims and straight-razor shaves.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'hero', 'lead_em', 'fr', 'Une chaise, une heure, pas de précipitation.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'hero', 'lead_em', 'nl', 'Eén stoel, één uur, geen haast.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'hero', 'lead_em', 'en', 'One chair, one hour, no rush.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'hero', 'cta_primary', 'fr', 'Prendre rendez-vous');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'hero', 'cta_primary', 'nl', 'Afspraak maken');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'hero', 'cta_primary', 'en', 'Book an appointment');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'hero', 'cta_secondary', 'fr', 'Voir la carte');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'hero', 'cta_secondary', 'nl', 'Bekijk de prijzen');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'hero', 'cta_secondary', 'en', 'See the menu');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'hero', 'fact1_label', 'fr', 'Rasage');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'hero', 'fact1_label', 'nl', 'Scheren');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'hero', 'fact1_label', 'en', 'Shave');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'hero', 'fact1_value', 'fr', 'Lame droite, serviette chaude');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'hero', 'fact1_value', 'nl', 'Recht scheermes, warme handdoek');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'hero', 'fact1_value', 'en', 'Straight razor, hot towel');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'hero', 'fact2_label', 'fr', 'Ouvert');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'hero', 'fact2_label', 'nl', 'Open');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'hero', 'fact2_label', 'en', 'Open');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'hero', 'fact2_value', 'fr', 'Mardi au samedi');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'hero', 'fact2_value', 'nl', 'Dinsdag tot zaterdag');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'hero', 'fact2_value', 'en', 'Tuesday to Saturday');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'hero', 'fact3_label', 'fr', 'Fauteuils');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'hero', 'fact3_label', 'nl', 'Stoelen');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'hero', 'fact3_label', 'en', 'Chairs');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'hero', 'fact3_value', 'fr', 'Trois, jamais plus');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'hero', 'fact3_value', 'nl', 'Drie, nooit meer');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'hero', 'fact3_value', 'en', 'Three, never more');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'maison', 'eyebrow', 'fr', 'La maison');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'maison', 'eyebrow', 'nl', 'Het huis');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'maison', 'eyebrow', 'en', 'The house');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'maison', 'title', 'fr', 'Un atelier, pas un salon');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'maison', 'title', 'nl', 'Een atelier, geen salon');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'maison', 'title', 'en', 'A workshop, not a salon');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'maison', 'body', 'fr', 'Le Corbeau est une petite boutique aux murs sombres, trois fauteuils en cuir et un miroir ancien. On y vient pour une coupe nette et une barbe dessinée à la main, dans le calme. Pas d''écran, pas de musique trop forte.

Chaque rendez-vous commence par une discussion : votre implantation, votre rythme de repousse, ce que vous voulez entretenir seul à la maison. Ensuite seulement viennent les ciseaux et la lame.

Nous travaillons avec des produits sobres et sans parfum envahissant : savon à barbe monté au blaireau, huile de cèdre, baume à l''alun.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'maison', 'body', 'nl', 'Le Corbeau is een kleine zaak met donkere muren, drie leren stoelen en een oude spiegel. Je komt hier voor een strakke snit en een met de hand getekende baard, in alle rust. Geen schermen, geen te luide muziek.

Elke afspraak begint met een gesprek: je haarinplanting, hoe snel je haar groeit, wat je thuis zelf wilt onderhouden. Pas daarna komen de schaar en het mes.

We werken met sobere producten zonder overheersende geur: scheerzeep opgeklopt met de kwast, cederolie, aluinbalsem.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'maison', 'body', 'en', 'Le Corbeau is a small shop with dark walls, three leather chairs and an old mirror. People come here for a clean cut and a hand-shaped beard, in peace and quiet. No screens, no loud music.

Every appointment starts with a conversation: your hairline, how fast your hair grows, what you want to maintain yourself at home. Only then come the scissors and the razor.

We use simple products with no overpowering scent: shaving soap whipped with a brush, cedar oil, alum balm.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'maison', 'rules_title', 'fr', 'Les règles de la maison');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'maison', 'rules_title', 'nl', 'De huisregels');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'maison', 'rules_title', 'en', 'House rules');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'maison', 'rules', 'fr', 'Une lame neuve pour chaque client.
Le temps annoncé est le temps passé au fauteuil.
On vous dit honnêtement si une coupe ne vous ira pas.
Le café est offert, le silence aussi.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'maison', 'rules', 'nl', 'Een nieuw mes voor elke klant.
De aangekondigde tijd is de tijd in de stoel.
We zeggen het eerlijk als een snit je niet zal staan.
De koffie is gratis, de stilte ook.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'maison', 'rules', 'en', 'A fresh blade for every client.
The time we announce is the time you spend in the chair.
We will tell you honestly if a cut won''t suit you.
Coffee is on the house, and so is the silence.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'carte', 'eyebrow', 'fr', 'La carte');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'carte', 'eyebrow', 'nl', 'De prijslijst');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'carte', 'eyebrow', 'en', 'The menu');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'carte', 'title', 'fr', 'Prestations & tarifs');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'carte', 'title', 'nl', 'Diensten & prijzen');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'carte', 'title', 'en', 'Services & prices');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'carte', 'intro', 'fr', 'Prix TVA comprise. La durée indiquée inclut le shampoing et le coiffage.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'carte', 'intro', 'nl', 'Prijzen inclusief btw. De vermelde duur omvat wassen en stylen.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'carte', 'intro', 'en', 'Prices include VAT. The time shown includes shampoo and styling.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'carte', 'note', 'fr', 'Carte cadeau disponible en boutique, de 25 € à 100 €.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'carte', 'note', 'nl', 'Cadeaubon verkrijgbaar in de zaak, van € 25 tot € 100.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'carte', 'note', 'en', 'Gift cards available in the shop, from €25 to €100.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'equipe', 'eyebrow', 'fr', 'Les mains');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'equipe', 'eyebrow', 'nl', 'De handen');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'equipe', 'eyebrow', 'en', 'The hands');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'equipe', 'title', 'fr', 'Nos barbiers');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'equipe', 'title', 'nl', 'Onze barbiers');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'equipe', 'title', 'en', 'Our barbers');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'reservation', 'eyebrow', 'fr', 'Rendez-vous');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'reservation', 'eyebrow', 'nl', 'Afspraak');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'reservation', 'eyebrow', 'en', 'Appointment');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'reservation', 'title', 'fr', 'Réserver un fauteuil');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'reservation', 'title', 'nl', 'Een stoel reserveren');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'reservation', 'title', 'en', 'Book a chair');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'reservation', 'intro', 'fr', 'Choisissez votre prestation, votre barbier et un créneau libre : la réservation est confirmée tout de suite.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'reservation', 'intro', 'nl', 'Kies je dienst, je barbier en een vrij tijdstip: je reservatie wordt meteen bevestigd.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'reservation', 'intro', 'en', 'Choose your service, your barber and a free slot: your booking is confirmed straight away.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'horaires', 'eyebrow', 'fr', 'Nous trouver');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'horaires', 'eyebrow', 'nl', 'Hoe ons vinden');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'horaires', 'eyebrow', 'en', 'Find us');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'horaires', 'title', 'fr', 'Horaires & adresse');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'horaires', 'title', 'nl', 'Openingsuren & adres');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'horaires', 'title', 'en', 'Opening hours & address');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'footer', 'tagline', 'fr', 'Barbier à Bruxelles — lame, vapeur et silence.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'footer', 'tagline', 'nl', 'Barbier in Brussel — mes, stoom en stilte.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'footer', 'tagline', 'en', 'Barber in Brussels — blade, steam and silence.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'legal_notice', 'title', 'fr', 'Mentions légales');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'legal_notice', 'title', 'nl', 'Juridische informatie');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'legal_notice', 'title', 'en', 'Legal notice');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'legal_notice', 'body', 'fr', '**Éditeur du site** : Le Corbeau, [forme juridique et nom de l''exploitant à compléter], Rue de l''Exemple 13, 1000 Bruxelles.

**Numéro d''entreprise (BCE)** : [à compléter]. **TVA** : [à compléter].

**Contact** : voir la section « Horaires & adresse ».

**Hébergement** : OVH SAS, 2 rue Kellermann, 59100 Roubaix, France.

Les textes et photos de ce site appartiennent au salon ; toute reproduction demande son accord.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'legal_notice', 'body', 'nl', '**Uitgever van de website**: Le Corbeau, [rechtsvorm en naam van de uitbater aan te vullen], Rue de l''Exemple 13, 1000 Brussel.

**Ondernemingsnummer (KBO)**: [aan te vullen]. **Btw**: [aan te vullen].

**Contact**: zie het onderdeel « Openingsuren & adres ».

**Hosting**: OVH SAS, 2 rue Kellermann, 59100 Roubaix, Frankrijk.

De teksten en foto''s op deze website zijn eigendom van de zaak; overname vereist haar toestemming.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'legal_notice', 'body', 'en', '**Site publisher**: Le Corbeau, [legal form and operator''s name to be completed], Rue de l''Exemple 13, 1000 Brussels.

**Company number (CBE)**: [to be completed]. **VAT**: [to be completed].

**Contact**: see the « Opening hours & address » section.

**Hosting**: OVH SAS, 2 rue Kellermann, 59100 Roubaix, France.

The texts and photos on this site belong to the shop; any reproduction requires its permission.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'privacy', 'title', 'fr', 'Politique de confidentialité');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'privacy', 'title', 'nl', 'Privacybeleid');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'privacy', 'title', 'en', 'Privacy policy');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'privacy', 'body', 'fr', '**Responsable du traitement** : Le Corbeau (voir les mentions légales).

**Données collectées** : lors d''une réservation, vos nom, téléphone et adresse e-mail, la prestation, le barbier et l''heure choisis, et la langue du site.

**Finalité et base légale** : gérer votre rendez-vous (vous le confirmer, vous permettre de l''annuler, vous prévenir d''un changement). La base légale est l''exécution du service que vous demandez.

**Durée de conservation** : vos données sont effacées automatiquement {{retention_months}} mois après la date du rendez-vous.

**Destinataires** : le salon uniquement. Les e-mails passent par le serveur d''envoi de notre hébergeur (UE). Aucune donnée n''est vendue ni utilisée à des fins publicitaires ; le site n''utilise ni traceur ni outil de mesure d''audience.

**Vos droits** : accès, rectification, effacement, opposition. Écrivez-nous à l''adresse e-mail indiquée dans la section « Horaires & adresse » ; nous répondons sous un mois. Vous pouvez aussi saisir l''Autorité de protection des données (autoriteprotectiondonnees.be).');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'privacy', 'body', 'nl', '**Verwerkingsverantwoordelijke**: Le Corbeau (zie de juridische informatie).

**Verzamelde gegevens**: bij een reservatie je naam, telefoon en e-mailadres, de gekozen dienst, barbier en tijd, en de taal van de website.

**Doel en rechtsgrond**: je afspraak beheren (bevestigen, annuleren mogelijk maken, je verwittigen bij een wijziging). De rechtsgrond is de uitvoering van de dienst die je vraagt.

**Bewaartermijn**: je gegevens worden automatisch gewist {{retention_months}} maanden na de datum van de afspraak.

**Ontvangers**: alleen de zaak. E-mails gaan via de verzendserver van onze hostingprovider (EU). Geen enkel gegeven wordt verkocht of voor reclame gebruikt; de website gebruikt geen trackers of meetinstrumenten.

**Je rechten**: inzage, verbetering, wissing, bezwaar. Schrijf ons op het e-mailadres in het onderdeel « Openingsuren & adres »; we antwoorden binnen een maand. Je kunt ook terecht bij de Gegevensbeschermingsautoriteit (gegevensbeschermingsautoriteit.be).');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('section', 'privacy', 'body', 'en', '**Data controller**: Le Corbeau (see the legal notice).

**Data collected**: when you book, your name, phone number and e-mail address, the service, barber and time you choose, and the language of the site.

**Purpose and legal basis**: managing your appointment (confirming it, letting you cancel it, telling you about a change). The legal basis is the performance of the service you request.

**Retention**: your data is erased automatically {{retention_months}} months after the date of the appointment.

**Recipients**: the shop only. E-mails go through our host''s sending server (EU). No data is sold or used for advertising; the site uses no trackers or audience measurement.

**Your rights**: access, rectification, erasure, objection. Write to us at the e-mail address in the « Opening hours & address » section; we reply within one month. You can also contact the Data Protection Authority (dataprotectionauthority.be).');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('salon', '1', 'address', 'fr', 'Rue de l''Exemple 13
1000 Bruxelles');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('salon', '1', 'address', 'nl', 'Rue de l''Exemple 13
1000 Brussel');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('salon', '1', 'address', 'en', 'Rue de l''Exemple 13
1000 Brussels');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('salon', '1', 'access', 'fr', 'Métro à 5 minutes à pied. Vélos bienvenus devant la vitrine.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('salon', '1', 'access', 'nl', 'Metro op 5 minuten wandelen. Fietsen welkom voor de etalage.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('salon', '1', 'access', 'en', 'Metro station a 5-minute walk away. Bikes welcome outside the window.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('category', 'c0000000-0000-4000-8000-000000000001', 'name', 'fr', 'Cheveux');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('category', 'c0000000-0000-4000-8000-000000000001', 'name', 'nl', 'Haar');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('category', 'c0000000-0000-4000-8000-000000000001', 'name', 'en', 'Hair');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('category', 'c0000000-0000-4000-8000-000000000002', 'name', 'fr', 'Barbe & rasage');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('category', 'c0000000-0000-4000-8000-000000000002', 'name', 'nl', 'Baard & scheren');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('category', 'c0000000-0000-4000-8000-000000000002', 'name', 'en', 'Beard & shave');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('service', '50000000-0000-4000-8000-000000000001', 'name', 'fr', 'Coupe classique');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('service', '50000000-0000-4000-8000-000000000001', 'name', 'nl', 'Klassieke snit');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('service', '50000000-0000-4000-8000-000000000001', 'name', 'en', 'Classic cut');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('service', '50000000-0000-4000-8000-000000000001', 'description', 'fr', 'Ciseaux et tondeuse, finitions au rasoir.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('service', '50000000-0000-4000-8000-000000000001', 'description', 'nl', 'Schaar en tondeuse, afwerking met het mes.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('service', '50000000-0000-4000-8000-000000000001', 'description', 'en', 'Scissors and clippers, razor finish.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('service', '50000000-0000-4000-8000-000000000002', 'name', 'fr', 'Coupe aux ciseaux');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('service', '50000000-0000-4000-8000-000000000002', 'name', 'nl', 'Knippen met de schaar');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('service', '50000000-0000-4000-8000-000000000002', 'name', 'en', 'Scissor cut');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('service', '50000000-0000-4000-8000-000000000002', 'description', 'fr', 'Entièrement aux ciseaux, pour les cheveux mi-longs.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('service', '50000000-0000-4000-8000-000000000002', 'description', 'nl', 'Volledig met de schaar, voor halflang haar.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('service', '50000000-0000-4000-8000-000000000002', 'description', 'en', 'Entirely with scissors, for medium-length hair.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('service', '50000000-0000-4000-8000-000000000003', 'name', 'fr', 'Dégradé à blanc');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('service', '50000000-0000-4000-8000-000000000003', 'name', 'nl', 'Skin fade');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('service', '50000000-0000-4000-8000-000000000003', 'name', 'en', 'Skin fade');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('service', '50000000-0000-4000-8000-000000000003', 'description', 'fr', 'Transition nette jusqu''à la peau.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('service', '50000000-0000-4000-8000-000000000003', 'description', 'nl', 'Strakke overgang tot op de huid.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('service', '50000000-0000-4000-8000-000000000003', 'description', 'en', 'A clean transition down to the skin.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('service', '50000000-0000-4000-8000-000000000004', 'name', 'fr', 'Enfant, moins de 12 ans');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('service', '50000000-0000-4000-8000-000000000004', 'name', 'nl', 'Kind, jonger dan 12');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('service', '50000000-0000-4000-8000-000000000004', 'name', 'en', 'Child, under 12');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('service', '50000000-0000-4000-8000-000000000004', 'description', 'fr', 'Coupe simple, en douceur.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('service', '50000000-0000-4000-8000-000000000004', 'description', 'nl', 'Eenvoudige snit, zachtjes.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('service', '50000000-0000-4000-8000-000000000004', 'description', 'en', 'A simple, gentle cut.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('service', '50000000-0000-4000-8000-000000000005', 'name', 'fr', 'Taille de barbe');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('service', '50000000-0000-4000-8000-000000000005', 'name', 'nl', 'Baard trimmen');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('service', '50000000-0000-4000-8000-000000000005', 'name', 'en', 'Beard trim');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('service', '50000000-0000-4000-8000-000000000005', 'description', 'fr', 'Tracé des contours à la lame, huile de cèdre.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('service', '50000000-0000-4000-8000-000000000005', 'description', 'nl', 'Contouren met het mes, cederolie.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('service', '50000000-0000-4000-8000-000000000005', 'description', 'en', 'Lines shaped with the razor, cedar oil.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('service', '50000000-0000-4000-8000-000000000006', 'name', 'fr', 'Rasage à l''ancienne');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('service', '50000000-0000-4000-8000-000000000006', 'name', 'nl', 'Traditioneel scheren');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('service', '50000000-0000-4000-8000-000000000006', 'name', 'en', 'Traditional shave');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('service', '50000000-0000-4000-8000-000000000006', 'description', 'fr', 'Deux serviettes chaudes, savon au blaireau, lame droite, baume.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('service', '50000000-0000-4000-8000-000000000006', 'description', 'nl', 'Twee warme handdoeken, zeep met de kwast, recht mes, balsem.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('service', '50000000-0000-4000-8000-000000000006', 'description', 'en', 'Two hot towels, brush-whipped soap, straight razor, balm.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('service', '50000000-0000-4000-8000-000000000007', 'name', 'fr', 'Coupe & barbe');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('service', '50000000-0000-4000-8000-000000000007', 'name', 'nl', 'Snit & baard');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('service', '50000000-0000-4000-8000-000000000007', 'name', 'en', 'Cut & beard');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('service', '50000000-0000-4000-8000-000000000007', 'description', 'fr', 'La coupe classique et la taille, en un seul passage.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('service', '50000000-0000-4000-8000-000000000007', 'description', 'nl', 'De klassieke snit en het trimmen, in één beurt.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('service', '50000000-0000-4000-8000-000000000007', 'description', 'en', 'The classic cut and the trim, in one visit.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('service', '50000000-0000-4000-8000-000000000008', 'name', 'fr', 'Le Rituel du Corbeau');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('service', '50000000-0000-4000-8000-000000000008', 'name', 'nl', 'Het Corbeau-ritueel');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('service', '50000000-0000-4000-8000-000000000008', 'name', 'en', 'The Corbeau Ritual');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('service', '50000000-0000-4000-8000-000000000008', 'description', 'fr', 'Coupe, rasage à l''ancienne, soin du visage et massage du cuir chevelu.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('service', '50000000-0000-4000-8000-000000000008', 'description', 'nl', 'Snit, traditioneel scheren, gezichtsverzorging en hoofdhuidmassage.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('service', '50000000-0000-4000-8000-000000000008', 'description', 'en', 'Cut, traditional shave, facial care and scalp massage.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('barber', 'b0000000-0000-4000-8000-000000000001', 'specialty', 'fr', 'Fondateur');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('barber', 'b0000000-0000-4000-8000-000000000001', 'specialty', 'nl', 'Oprichter');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('barber', 'b0000000-0000-4000-8000-000000000001', 'specialty', 'en', 'Founder');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('barber', 'b0000000-0000-4000-8000-000000000001', 'bio', 'fr', 'Dix-huit ans de métier, formé à Anvers. Spécialiste du rasage à la lame droite.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('barber', 'b0000000-0000-4000-8000-000000000001', 'bio', 'nl', 'Achttien jaar ervaring, opgeleid in Antwerpen. Specialist in scheren met het rechte mes.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('barber', 'b0000000-0000-4000-8000-000000000001', 'bio', 'en', 'Eighteen years in the trade, trained in Antwerp. Straight-razor shave specialist.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('barber', 'b0000000-0000-4000-8000-000000000002', 'specialty', 'fr', 'Coupes aux ciseaux');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('barber', 'b0000000-0000-4000-8000-000000000002', 'specialty', 'nl', 'Knippen met de schaar');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('barber', 'b0000000-0000-4000-8000-000000000002', 'specialty', 'en', 'Scissor cuts');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('barber', 'b0000000-0000-4000-8000-000000000002', 'bio', 'fr', 'Cheveux épais, bouclés ou frisés : il lit la texture avant de couper.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('barber', 'b0000000-0000-4000-8000-000000000002', 'bio', 'nl', 'Dik, golvend of krullend haar: hij leest de structuur voor hij knipt.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('barber', 'b0000000-0000-4000-8000-000000000002', 'bio', 'en', 'Thick, wavy or curly hair: he reads the texture before he cuts.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('barber', 'b0000000-0000-4000-8000-000000000003', 'specialty', 'fr', 'Barbes & dégradés');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('barber', 'b0000000-0000-4000-8000-000000000003', 'specialty', 'nl', 'Baarden & fades');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('barber', 'b0000000-0000-4000-8000-000000000003', 'specialty', 'en', 'Beards & fades');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('barber', 'b0000000-0000-4000-8000-000000000003', 'bio', 'fr', 'Dégradés précis et barbes longues sculptées. Elle vous apprend aussi l''entretien.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('barber', 'b0000000-0000-4000-8000-000000000003', 'bio', 'nl', 'Precieze fades en gebeeldhouwde lange baarden. Ze leert je ook het onderhoud.');
INSERT INTO translation (owner_type, owner_id, field, lang, value) VALUES ('barber', 'b0000000-0000-4000-8000-000000000003', 'bio', 'en', 'Precise fades and sculpted long beards. She also teaches you how to maintain them.');
