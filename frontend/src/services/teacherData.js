// Lokale Demo-Daten für Lehrer-Dashboard (Subjects, Schüler, Leistungsfeststellungen)
// Nutzt localStorage zum Überschreiben, damit du schnell editieren kannst.

const STORAGE_KEY = 'tg-teacher-demo'

const sample = {
  teacher: {
    id: 1,
    name: 'Prof. Novak',
    email: 'novak@tg.at'
  },
  students: [
    { id: 1, vorname: 'Max', nachname: 'Mustermann', klasse: '3AI' },
    { id: 2, vorname: 'Lena', nachname: 'Hart', klasse: '3AI' },
    { id: 3, vorname: 'Eliott', nachname: 'Berger', klasse: '3AI' },
    { id: 4, vorname: 'Alex', nachname: 'Lamper', klasse: '3AI' },
    { id: 5, vorname: 'Sara', nachname: 'Weinberger', klasse: '3AI' }
  ],
  subjects: [
    { id: 101, title: '2025/26 Softwareentwicklung 3AI', short: 'SE 3AI', klasse: '3AI', img: '/cards/card-se.svg', color: '#7A79E9', trend: [2.3, 2.1, 2.8, 3.2, 2.5, 2.9, 2.7, 2.4, 2.6, 2.3, 2.5, 2.4] },
    { id: 102, title: '2025/26 Medientechnik 4BI', short: 'MT 4BI', klasse: '4BI', img: '/cards/card-mt.svg', color: '#52A0D7', trend: [3.1, 3.0, 3.3, 3.0, 2.8, 2.9, 3.0, 3.1, 3.0, 2.9, 2.8, 2.7] },
    { id: 103, title: '2025/26 Netzwerktechnik 4CH', short: 'NT 4CH', klasse: '4CH', img: '/cards/card-nt.svg', color: '#46A687', trend: [2.9, 2.7, 2.6, 2.5, 2.8, 2.7, 2.9, 2.6, 2.5, 2.7, 2.8, 2.6] }
  ],
  assessments: [
    {
      id: 201,
      subjectId: 101,
      title: 'PLF Objektorientiertes Programmieren',
      typ: 'PLF',
      datum: '2025-05-04',
      maxPoints: 24,
      gewichtung: 1,
      results: [
        { studentId: 1, points: 17, comment: 'Saubere Lösung' },
        { studentId: 2, points: 15, comment: 'Aufgabe 3 fehlt' },
        { studentId: 3, points: 12, comment: 'Teilweise richtig' },
        { studentId: 4, points: 10, comment: 'Nachbesprechen' },
        { studentId: 5, points: 21, comment: 'Sehr stark!' }
      ]
    },
    {
      id: 204,
      subjectId: 101,
      title: 'PLF Software Patterns',
      typ: 'PLF',
      datum: '2025-09-20',
      maxPoints: 24,
      gewichtung: 1,
      results: [
        { studentId: 1, points: 18, comment: '' },
        { studentId: 2, points: 15, comment: '' },
        { studentId: 3, points: 14, comment: '' },
        { studentId: 4, points: 13, comment: '' },
        { studentId: 5, points: 20, comment: '' }
      ]
    },
    {
      id: 205,
      subjectId: 101,
      title: 'PLF Software Architektur',
      typ: 'PLF',
      datum: '2025-12-15',
      maxPoints: 24,
      gewichtung: 1,
      results: [
        { studentId: 1, points: 20, comment: '' },
        { studentId: 2, points: 17, comment: '' },
        { studentId: 3, points: 16, comment: '' },
        { studentId: 4, points: 14, comment: '' },
        { studentId: 5, points: 22, comment: '' }
      ]
    },
    {
      id: 202,
      subjectId: 101,
      title: 'Schularbeit Algorithmen',
      typ: 'SA',
      datum: '2025-01-15',
      maxPoints: 24,
      gewichtung: 1.3,
      results: [
        { studentId: 1, points: 13, comment: '' },
        { studentId: 2, points: 11, comment: '' },
        { studentId: 3, points: 16, comment: '' },
        { studentId: 4, points: 9, comment: '' },
        { studentId: 5, points: 18, comment: '' }
      ]
    },
    {
      id: 301,
      subjectId: 102,
      title: 'Projekt Video-Editing',
      typ: 'Projekt',
      datum: '2025-04-11',
      maxPoints: 30,
      gewichtung: 1,
      results: [
        { studentId: 2, points: 25, comment: '' },
        { studentId: 3, points: 21, comment: '' }
      ]
    }
  ]
}

function load() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    if (!raw) return sample
    return JSON.parse(raw)
  } catch (e) {
    console.warn('Konnte Demo-Daten nicht lesen, verwende Defaults.', e)
    return sample
  }
}

function save(data) {
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(data))
  } catch (e) {
    console.warn('Konnte Demo-Daten nicht speichern.', e)
  }
}

export function getSubjects() {
  return load().subjects
}

export function getSubjectById(id) {
  return load().subjects.find(s => String(s.id) === String(id))
}

export function getStudents() {
  return load().students
}

export function getAssessmentsForSubject(subjectId) {
  return load().assessments.filter(a => String(a.subjectId) === String(subjectId))
}

export function getTrendForSubject(subjectId) {
  const subj = getSubjectById(subjectId)
  return subj?.trend || []
}

export function getStudentsByClass(klasse) {
  const data = load()
  if (!klasse) return data.students
  return data.students.filter(s => s.klasse === klasse)
}

export function updateAssessment(subjectId, assessmentId, updater) {
  const data = load()
  const idx = data.assessments.findIndex(a => String(a.id) === String(assessmentId) && String(a.subjectId) === String(subjectId))
  if (idx === -1) return null
  const updated = updater({ ...data.assessments[idx] })
  data.assessments[idx] = updated
  save(data)
  return updated
}

export function addAssessment(subjectId, payload) {
  const data = load()
  const newAssessment = {
    ...payload,
    id: Date.now(),
    subjectId,
    results: payload.results || []
  }
  data.assessments.unshift(newAssessment)
  save(data)
  return newAssessment
}

export function upsertStudent(student) {
  const data = load()
  const exists = data.students.findIndex(s => s.id === student.id)
  if (exists >= 0) data.students[exists] = student
  else data.students.push({ ...student, id: Date.now() })
  save(data)
  return student
}

export default {
  getSubjects,
  getSubjectById,
  getStudents,
  getAssessmentsForSubject,
  getTrendForSubject,
  getStudentsByClass,
  updateAssessment,
  addAssessment,
  upsertStudent
}
