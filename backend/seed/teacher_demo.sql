-- Demo-Daten für Lehrer-Dashboard (lokal). IDs bewusst hoch gewählt, damit sie nicht mit bestehenden kollidieren.

INSERT INTO tbl_Microsoft365_User (id, nachname, vorname, email) VALUES
  (9001, 'Novak', 'Petra', 'petra.novak@transparentgrading.test'),
  (9002, 'Mustermann', 'Max', 'max.mustermann@transparentgrading.test'),
  (9003, 'Hart', 'Lena', 'lena.hart@transparentgrading.test'),
  (9004, 'Berger', 'Eliott', 'eliott.berger@transparentgrading.test'),
  (9005, 'Lamper', 'Alex', 'alex.lamper@transparentgrading.test'),
  (9006, 'Weinberger', 'Sara', 'sara.weinberger@transparentgrading.test')
ON DUPLICATE KEY UPDATE email = VALUES(email);

INSERT INTO Lehrer (id, vorname, nachname, fach, ms365usr_id) VALUES
  (5001, 'Petra', 'Novak', 'Softwareentwicklung', 9001)
ON DUPLICATE KEY UPDATE fach = VALUES(fach);

INSERT INTO Klassen (id, name) VALUES
  (3001, '3AI'),
  (3002, '4BI'),
  (3003, '4CH')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO Faecher (id, name) VALUES
  (4001, 'Softwareentwicklung'),
  (4002, 'Medientechnik'),
  (4003, 'Netzwerktechnik')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO Kurse (id, name, fach_id, lehrer_id, klasse_id) VALUES
  (6001, '2025/26 Softwareentwicklung 3AI', 4001, 5001, 3001),
  (6002, '2025/26 Medientechnik 4BI', 4002, 5001, 3002),
  (6003, '2025/26 Netzwerktechnik 4CH', 4003, 5001, 3003)
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO Schueler (id, vorname, nachname, geburtsdatum, klasse_id, ms365usr_id) VALUES
  (2001, 'Max', 'Mustermann', '2007-04-12', 3001, 9002),
  (2002, 'Lena', 'Hart', '2007-09-02', 3001, 9003),
  (2003, 'Eliott', 'Berger', '2007-01-25', 3001, 9004),
  (2004, 'Alex', 'Lamper', '2007-11-15', 3001, 9005),
  (2005, 'Sara', 'Weinberger', '2007-03-08', 3001, 9006)
ON DUPLICATE KEY UPDATE nachname = VALUES(nachname);

INSERT INTO Kurs_Schueler (kurs_id, schueler_id) VALUES
  (6001, 2001),
  (6001, 2002),
  (6001, 2003),
  (6001, 2004),
  (6001, 2005)
ON DUPLICATE KEY UPDATE schueler_id = VALUES(schueler_id);
