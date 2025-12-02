-- Beispiel-PLFs für Fach Softwareentwicklung (4001) im Kurs 6001 (3AI)
-- Enthält zwei Termine (September + Dezember), damit der Monatsverlauf sichtbar ist.

-- Benotungsart PLF anlegen, falls noch nicht vorhanden
INSERT INTO Benotungsarten (id, name, gewichtung)
VALUES (9001, 'PLF', 1.00)
ON DUPLICATE KEY UPDATE name = VALUES(name), gewichtung = VALUES(gewichtung);

-- PLF-Einträge für drei Schüler (IDs aus teacher_demo.sql)
INSERT INTO Benotung (id, schueler_id, fach_id, lehrer_id, datum, typ, note, kommentar) VALUES
  (9101, 2001, 4001, 5001, '2025-09-20', 9001, 2.30, 'PLF 1'),
  (9102, 2002, 4001, 5001, '2025-09-20', 9001, 3.00, 'PLF 1'),
  (9103, 2003, 4001, 5001, '2025-09-20', 9001, 2.70, 'PLF 1'),
  (9104, 2001, 4001, 5001, '2025-12-15', 9001, 2.10, 'PLF 2'),
  (9105, 2002, 4001, 5001, '2025-12-15', 9001, 2.50, 'PLF 2'),
  (9106, 2003, 4001, 5001, '2025-12-15', 9001, 2.30, 'PLF 2');

-- Falls weitere Schüler mitmachen sollen, hier ergänzen:
-- (9107, <schueler_id>, 4001, 5001, '2025-09-20', 9001, 2.80, 'PLF 1');

