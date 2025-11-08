<template>
    <div class="klassen-view">
        <!-- Überschrift -->
        

        <!-- obere Steuerleiste -->
        <div class="toolbar">

            <div class="left-controls">
<h1 class="title">Datenschutzkonzept</h1>
            </div>
            <div class="right-controls">

                <button class="btn create-btn" @click="downloadPDF">
                    PDF herunterladen
                </button>
            </div>
        </div>


        <div class="container">
            <header>
                <h1>Datenschutzkonzept — Transparent Grading</h1>
                <div class="meta">
                    <strong>Projekt:</strong> Transparent Grading – Die Notenübersicht von Morgen<br>
                    <strong>Schule:</strong> HTL Wien 3 Rennweg<br>
                    <strong>Autor:</strong> Lara Ehart<br>
                    <strong>Version:</strong> 1.0<br>
                    <strong>Erstellt am:</strong> 30.10.2025<br>
                    <strong>Letzte Überprüfung:</strong> <span class="muted">(eintragen)</span><br>
                    <strong>Nächste geplante Überprüfung:</strong> <span class="muted">(eintragen)</span>
                </div>
            </header>

            <nav class="toc">
                <h2>INHALTSVERZEICHNIS</h2>
                <ul>
                    <li><a href="#vorwort">Vorwort</a></li>
                    <li><a href="#zweck">Zweck und Ziel des Dokuments</a></li>
                    <li><a href="#beschreibung">Beschreibung der Datenverarbeitung</a></li>
                    <li><a href="#rechtsgrundlage">Rechtsgrundlage der Verarbeitung</a></li>
                    <li><a href="#verantwortlichkeiten">Verantwortlichkeiten</a></li>
                    <li><a href="#speicherdauer">Speicherdauer und Löschung</a></li>
                    <li><a href="#rollen">Rollen- und Berechtigungskonzept</a></li>
                    <li><a href="#toms">Technische und Organisatorische Maßnahmen (TOMs)</a></li>
                    <li><a href="#weitergabe">Weitergabe und Drittstaatübermittlung</a></li>
                    <li><a href="#profiling">Profiling und automatisierte Entscheidungen</a></li>
                    <li><a href="#rechte">Rechte der betroffenen Personen</a></li>
                    <li><a href="#data-breach">Datenschutzverletzung („Data Breach")</a></li>
                    <li><a href="#dpo">Datenschutzbeauftragter und Informationspflicht</a></li>
                    <li><a href="#bewertung">Bewertung der Rechtmäßigkeit</a></li>
                    <li><a href="#zusammenfassung">Zusammenfassung</a></li>
                </ul>
            </nav>

            <section id="vorwort">
                <h2>VORWORT</h2>
                <p>Das vorliegende Datenschutzkonzept wurde im Rahmen der Diplomarbeit <em>Transparent Grading – Die
                        Notenübersicht von Morgen</em> an der HTL Wien 3 Rennweg erstellt. Es beschreibt die
                    rechtlichen, technischen und organisatorischen Maßnahmen, die zur Einhaltung der
                    Datenschutz-Grundverordnung (DSGVO) und der einschlägigen schulrechtlichen Bestimmungen umgesetzt
                    werden.</p>
                <p>Ziel des Projekts ist es, eine datenschutzkonforme Webanwendung zu entwickeln, die den Prozess der
                    Notenverwaltung an Schulen vereinfacht und zugleich die Transparenz für Schüler:innen erhöht. Dabei
                    steht der Schutz personenbezogener Daten – insbesondere von Schüler:innen und Lehrkräften – im
                    Vordergrund. Das Konzept dient sowohl als Nachweis für die DSGVO-Konformität des Projekts als auch
                    als Leitfaden für den sicheren Betrieb der Anwendung auf dem Schulserver der HTL Wien 3 Rennweg.</p>
            </section>

            <section id="zweck">
                <h2>Zweck und Ziel des Dokuments</h2>
                <p>Dieses Datenschutzkonzept beschreibt die datenschutzrechtlichen Rahmenbedingungen sowie technische
                    und organisatorische Maßnahmen (TOMs) für das Diplomarbeitsprojekt Transparent Grading. Ziel ist die
                    DSGVO-konforme Verarbeitung personenbezogener Daten (insbesondere Schüler:innendaten) im Rahmen
                    einer webbasierten Notenverwaltungsanwendung, die auf dem Schulserver betrieben wird.</p>
                <p>Während der Entwicklungszeit werden ausschließlich Testdaten verwendet. Ein späterer produktiver
                    Einsatz mit echten Schüler:innendaten erfolgt ausschließlich unter Verantwortung der Schule.</p>
            </section>

            <section id="beschreibung">
                <h2>Beschreibung der Datenverarbeitung</h2>

                <h3>Zweck der Verarbeitung</h3>
                <p>Erfassung, Verwaltung und transparente Darstellung von Leistungsdaten durch Lehrkräfte sowie die
                    Einsicht dieser Daten durch Schüler:innen.</p>

                <h3>Verarbeitete Datenkategorien</h3>
                <ul>
                    <li>Nachname, Vorname</li>
                    <li>Klassenbezeichnung</li>
                    <li>Schul-E-Mailadresse</li>
                    <li>Fach, Noten, Gewichtung, Leistungsart, Datum</li>
                    <li>Benutzerrolle (Schüler:in, Lehrer:in, Admin)</li>
                    <li>Zeitstempel (Erstellung, Änderung)</li>
                    <li>Optionale Eltern-Emailadresse bei minderjährigen Schüler:innen</li>
                </ul>

                <h3>Systemumgebung</h3>
                <p>Die Anwendung läuft auf einem Schulserver im lokalen Netzwerk der HTL Wien 3 Rennweg. Der Zugriff
                    erfolgt ausschließlich über das Microsoft-Schulkonto (Single Sign-On). Alle Daten werden intern
                    gespeichert und nicht an Dritte übermittelt.</p>
            </section>

            <section id="rechtsgrundlage">
                <h2>Rechtsgrundlage der Verarbeitung</h2>
                <ul>
                    <li>Art. 6 Abs. 1 lit. e DSGVO – Verarbeitung ist zur Wahrnehmung einer Aufgabe im öffentlichen
                        Interesse erforderlich.</li>
                    <li>§ 77 Schulunterrichtsgesetz (SchUG) – Schulen sind verpflichtet, Leistungen von Schüler:innen zu
                        dokumentieren und zu bewerten.</li>
                    <li>Art. 6 Abs. 1 lit. a DSGVO – Einwilligung für optionale Funktionen (z. B. Elternbenachrichtigung
                        per E-Mail).</li>
                </ul>
                <p>Die Verarbeitung personenbezogener Daten ist somit rechtmäßig und zweckgebunden. Eine Verarbeitung
                    besonderer Kategorien personenbezogener Daten gemäß Art. 9 DSGVO erfolgt nicht.</p>
            </section>

            <section id="verantwortlichkeiten">
                <h2>Verantwortlichkeiten</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Rolle</th>
                            <th>Bezeichnung</th>
                            <th>Beschreibung</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Verantwortlicher</td>
                            <td>HTL Wien 3 Rennweg</td>
                            <td>Entscheidet über Zweck und Mittel der Verarbeitung.</td>
                        </tr>
                        <tr>
                            <td>Auftragsverarbeiter</td>
                            <td>Projektteam „Transparent Grading"</td>
                            <td>Entwickelt und betreibt die Software im Auftrag der Schule.</td>
                        </tr>
                        <tr>
                            <td>Betroffene Personen</td>
                            <td>Schüler:innen, Lehrer:innen</td>
                            <td>Deren personenbezogene Daten werden verarbeitet.</td>
                        </tr>
                        <tr>
                            <td>Datenschutzbeauftragter</td>
                            <td>Bildungsdirektion Wien</td>
                            <td>Überwacht Einhaltung der DSGVO und berät den Verantwortlichen.</td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <section id="speicherdauer">
                <h2>Speicherdauer und Löschung</h2>
                <p>Daten werden nur so lange gespeichert, wie sie für die Erfüllung des schulischen Zwecks erforderlich
                    sind.</p>
                <p>Nach Ablauf gesetzlicher Aufbewahrungsfristen (Schuljahr + Einspruchspflicht) erfolgt automatische
                    Löschung aller Daten. Testdaten werden nach Abschluss der Entwicklung vollständig gelöscht. Eine
                    manuelle Löschung von Schüler:innen oder Lehrer:innen und deren gesamten Daten ist für
                    Administrator:innen möglich.</p>
            </section>

            <section id="rollen">
                <h2>Rollen- und Berechtigungskonzept</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Rolle</th>
                            <th>Berechtigung</th>
                            <th>Beschreibung</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Schüler:in</td>
                            <td>Leserechte</td>
                            <td>Kann nur eigene Leistungen und Berechnungen einsehen.</td>
                        </tr>
                        <tr>
                            <td>Lehrer:in</td>
                            <td>Lese- und Schreibrechte</td>
                            <td>Kann Leistungen der eigenen Schüler:innen erfassen, einsehen, bearbeiten und löschen.
                            </td>
                        </tr>
                        <tr>
                            <td>Administrator:in</td>
                            <td>Technische Rechte</td>
                            <td>Systempflege (Schüler:innen, Lehrer:innen), aber keine Einsicht in Leistungsdaten.</td>
                        </tr>
                        <tr>
                            <td>Projektteam</td>
                            <td>Testrechte</td>
                            <td>Zugriff nur auf Testdaten.</td>
                        </tr>
                    </tbody>
                </table>
                <p>Die Rollen werden automatisch aus dem Microsoft Active Directory der Schule zugewiesen. Jeder Zugriff
                    ist durch eine Authentifizierung über das Microsoft-Schulkonto abgesichert.</p>
            </section>

            <section id="toms">
                <h2>Technische und Organisatorische Maßnahmen (TOMs)</h2>
                <h3>Technische Maßnahmen</h3>
                <ul>
                    <li>Passwortschutz und Zwei-Faktor-Authentifizierung (Microsoft SSO)</li>
                    <li>Verschlüsselung der Datenbankverbindungen</li>
                    <li>Regelmäßige Backups auf internen Systemen</li>
                    <li>Zugriffsbeschränkung über Rollen und Berechtigungen</li>
                    <li>Keine Cloudspeicherung, keine externe Datenübertragung</li>
                </ul>

                <h3>Organisatorische Maßnahmen</h3>
                <ul>
                    <li>Zugriff nur für berechtigte Personen (Schüler:innen, Lehrkräfte, IT-Admin)</li>
                    <li>Schulung des Entwicklerteams zu DSGVO und IT-Sicherheit</li>
                    <li>Regelmäßige Überprüfung der Berechtigungen</li>
                    <li>Protokollierung von Änderungen</li>
                </ul>
            </section>

            <section id="weitergabe">
                <h2>Weitergabe und Drittstaatübermittlung</h2>
                <p>Es erfolgt keine Weitergabe der Daten an Dritte. Die Daten werden ausschließlich innerhalb der EU
                    verarbeitet. Sollte in Zukunft ein externer Dienst verwendet werden (z. B. E-Mail-Provider), wird
                    ein Auftragsverarbeitungsvertrag nach Art. 28 DSGVO abgeschlossen.</p>
            </section>

            <section id="profiling">
                <h2>Profiling und automatisierte Entscheidungen</h2>
                <p>Im Sinne von Art. 4 Abs. 4 und Art. 22 DSGVO erfolgt in der Anwendung Transparent Grading kein
                    Profiling und keine automatisierte Entscheidungsfindung. Alle Bewertungen und Noten werden
                    ausschließlich von Lehrkräften manuell eingetragen und überprüft. Die Anwendung stellt lediglich
                    Berechnungen und grafische Darstellungen bereit, trifft jedoch keine eigenständigen Entscheidungen
                    über Schüler:innen oder deren Leistungen.</p>
            </section>

            <section id="rechte">
                <h2>Rechte der betroffenen Personen</h2>
                <p>Schüler:innen und Lehrer:innen haben gemäß DSGVO folgende Rechte:</p>
                <ul>
                    <li>Auskunftsrecht (Art. 15)</li>
                    <li>Berichtigung (Art. 16)</li>
                    <li>Löschung (Art. 17)</li>
                    <li>Einschränkung der Verarbeitung (Art. 18)</li>
                    <li>Widerspruchsrecht (Art. 21)</li>
                </ul>
                <p>Diese Rechte können durch die betroffenen Personen über die Schulleitung oder den
                    Datenschutzbeauftragten der Bildungsdirektion Wien geltend gemacht werden. Ein entsprechender
                    Hinweis wird in der Navigation der Anwendung bereitgestellt.</p>
            </section>

            <section id="data-breach">
                <h2>Datenschutzverletzung („Data Breach")</h2>
                <p>Im Falle einer Datenschutzverletzung (z. B. Hackerangriff oder Fehlkonfiguration):</p>
                <ul>
                    <li>Meldung an die Datenschutzbehörde innerhalb von 72 Stunden durch den Verantwortlichen (Schule).
                    </li>
                    <li>Dokumentation des Vorfalls (Art, Umfang, betroffene Daten, Maßnahmen).</li>
                    <li>Umsetzung von Verbesserungsmaßnahmen durch das IT-Team.</li>
                </ul>
            </section>

            <section id="dpo">
                <h2>Datenschutzbeauftragter und Informationspflicht</h2>
                <p><strong>Verantwortlicher:</strong><br>
                    HTL Wien 3 Rennweg, Rennweg 89b, 1030 Wien</p>

                <p><strong>Datenschutzbeauftragter:</strong><br>
                    Bildungsdirektion Wien – Abteilung Datenschutz<br>
                    Email: <a href="mailto:datenschutz@bildung.gv.at">datenschutz@bildung.gv.at</a></p>

                <p><strong>Informationspflicht:</strong><br>
                    Schüler:innen und Lehrer:innen werden beim Login bzw. in der Navigation der Anwendung über die
                    Datenschutzinformationen informiert.</p>
            </section>

            <section id="bewertung">
                <h2>Bewertung der Rechtmäßigkeit</h2>
                <p>Die Datenverarbeitung im Rahmen von Transparent Grading erfolgt auf einer klaren gesetzlichen
                    Grundlage (Art. 6 Abs. 1 lit. e DSGVO i.V.m. § 77 SchUG). Sie ist zweckgebunden, verhältnismäßig und
                    durch technische sowie organisatorische Maßnahmen hinreichend geschützt. Damit ist die Verarbeitung
                    rechtmäßig, transparent und datenschutzkonform.</p>
            </section>

            <section id="zusammenfassung">
                <h2>ZUSAMMENFASSUNG</h2>
                <p>Das Projekt Transparent Grading erfüllt die Grundsätze der DSGVO:</p>
                <ul>
                    <li>Rechtmäßigkeit</li>
                    <li>Verarbeitung nach Treu und Glauben</li>
                    <li>Zweckbindung</li>
                    <li>Datenminimierung</li>
                    <li>Richtigkeit</li>
                    <li>Speicherbegrenzung</li>
                    <li>Integrität und Vertraulichkeit</li>
                </ul>
                <p class="footnote">Durch das vorliegende Datenschutzkonzept ist sichergestellt, dass alle
                    datenschutzrechtlichen Anforderungen bei der Entwicklung und dem zukünftigen Betrieb des Systems
                    berücksichtigt werden.</p>
            </section>

        </div>



    </div>







</template>

<script setup>

function downloadPDF() {
    const url = '/TransparentGrading_Datenschutzkonzept_v1.0.pdf'
    const a = document.createElement('a')
    a.href = url
    a.download = 'TransparentGrading_Datenschutzkonzept_v1.0.pdf'
    document.body.appendChild(a)
    a.click()
    a.remove()
}

</script>

<style scoped>
header{margin-bottom:1.25rem}
h1{font-size:1.6rem;margin:0 0 .25rem}

h2{margin-top:1.25rem;color:var(--accent)}
h3{margin-top:1rem}
p{margin:.6rem 0}
.toc{margin:1rem 0;padding:0}
.toc ul{padding-left:1rem}
table{width:100%;border-collapse:collapse;margin:.75rem 0}
th,td{border:1px solid var(--second-background-color);padding:.5rem;text-align:left}
th{background:var(--second-background-color)}
a {color: var(--primary)}

.pdf-frame {
    width: 100%;
    height: 80vh;
    border: 1px solid #ccc;
    border-radius: 8px;
}

.klassen-view {
    padding: 1rem 2rem;
}

.title {
    font-size: 2rem;
    margin-bottom: 2rem;
    text-align: left;
    font-weight: 650;
}

/* Toolbar */
.toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2.2rem;
}

.left-controls,
.right-controls {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Buttons */
.btn {
    background-color: var(--first-background-color);
    border: 1.5px solid var(--second-background-color);
    border-radius: 20px;
    padding: 0.4rem 0.8rem;
    cursor: pointer;
    transition: background-color 0.2s;
    padding: 16px 30px;
    min-width: 180px;
    color: var(--text);
}

.btn:hover {
    background-color: var(--second-background-color);
}

.create-btn {
    background-image: linear-gradient(to right, var(--primary), var(--secondary));
    color: var(--white);
    border: none;
}
</style>
