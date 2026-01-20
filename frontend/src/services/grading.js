
import axios from 'axios'

const STORAGE_KEY = 'gradingScheme'

/**
 * Standard-Schema (Fallback)
 * scoreType: 'grades' (1-5) oder 'points' (0-maxPoints)
 * maxPoints: maximale Punktzahl, falls scoreType='points' (z.B. 100)
 * gradeBands: Prozent → Note (passend zum UI: 60% ≈ Note 4)
 */
const DEFAULT_SCHEME = {
  mode: 'per-item', // 'per-item' | 'group'
  scoreType: 'grades', // 'grades' | 'points'
  maxPoints: 100, // nur relevant wenn scoreType='points'
  gradeBands: [
    { min: 92, grade: 1 },
    { min: 81, grade: 2 },
    { min: 65, grade: 3 },
    { min: 50, grade: 4 },
    { min: 0, grade: 5 }
  ],
  categories: [] // für group-mode
}

/**
 * Lädt das aktuell gespeicherte Schema (localStorage). Falls nichts
 * vorhanden, wird DEFAULT_SCHEME zurückgegeben.
 * @returns {Object}
 */
export function loadScheme() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    if (!raw) return { ...DEFAULT_SCHEME }
    return JSON.parse(raw)
  } catch (err) {
    console.error('Fehler beim Laden des Grading-Schemas aus localStorage', err)
    return { ...DEFAULT_SCHEME }
  }
}

/**
 * Multi-Schema support: store multiple named schemes and an active id
 */
const STORAGE_KEY_SCHEMES = 'gradingSchemes'
const STORAGE_KEY_ACTIVE = 'gradingActiveSchemeId'
const STORAGE_KEY_ACTIVE_BY_COURSE = 'gradingActiveSchemeByCourse'

function loadAllSchemes() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY_SCHEMES)
    if (!raw) {
      // migrate single scheme if present
      const single = localStorage.getItem(STORAGE_KEY)
      if (single) {
        const s = JSON.parse(single)
        const defaultScheme = { id: 'default', name: 'Standard', scheme: s }
        localStorage.setItem(STORAGE_KEY_SCHEMES, JSON.stringify([defaultScheme]))
        return [defaultScheme]
      }
      const def = { id: 'default', name: 'Standard', scheme: DEFAULT_SCHEME }
      localStorage.setItem(STORAGE_KEY_SCHEMES, JSON.stringify([def]))
      return [def]
    }
    const parsed = JSON.parse(raw)
    if (!Array.isArray(parsed)) throw new Error('gradingSchemes is not an array')

    // Deduplicate exact duplicates (same name + same scheme)
    const seen = new Set()
    const unique = []
    for (const s of parsed) {
      try {
        const key = (s.name || '') + '::' + JSON.stringify(s.scheme || {})
        if (!seen.has(key)) {
          seen.add(key)
          unique.push(s)
        }
      } catch (e) {
        // on stringify error, keep item
        unique.push(s)
      }
    }

    // If we only have many auto-created placeholders like 'Neues Schema',
    // collapse to single default to restore original behaviour.
    const onlyPlaceholders = unique.length > 1 && unique.every(u => {
      const n = (u.name || '').toLowerCase()
      return n === 'neues schema' || n.startsWith('neues schema') || n === 'neues'
    })
    if (onlyPlaceholders) {
      const def = { id: 'default', name: 'Standard', scheme: DEFAULT_SCHEME }
      localStorage.setItem(STORAGE_KEY_SCHEMES, JSON.stringify([def]))
      localStorage.setItem(STORAGE_KEY_ACTIVE, 'default')
      return [def]
    }

    if (unique.length !== parsed.length) {
      // save deduped list to avoid re-rendering the same duplicates
      try { localStorage.setItem(STORAGE_KEY_SCHEMES, JSON.stringify(unique)) } catch (e) {}
    }

    return unique
  } catch (err) {
    console.error('Fehler beim Laden aller Schemas', err)
    // Repair: reset to single default schema to avoid corrupt state
    try {
      const def = { id: 'default', name: 'Standard', scheme: DEFAULT_SCHEME }
      localStorage.setItem(STORAGE_KEY_SCHEMES, JSON.stringify([def]))
      localStorage.setItem(STORAGE_KEY_ACTIVE, 'default')
    } catch (e) {
      console.error('Fehler beim Reparieren des gradingSchemes keys', e)
    }
    return [{ id: 'default', name: 'Standard', scheme: DEFAULT_SCHEME }]
  }
}

function saveAllSchemes(arr) {
  try {
    localStorage.setItem(STORAGE_KEY_SCHEMES, JSON.stringify(arr))
  } catch (err) {
    console.error('Fehler beim Speichern aller Schemas', err)
  }
}

function createScheme(name = 'Neues Schema', schemeObj = null) {
  const schemes = loadAllSchemes()
  const id = String(Date.now())
  const s = { id, name, scheme: schemeObj || { ...DEFAULT_SCHEME } }
  schemes.push(s)
  saveAllSchemes(schemes)
  return s
}

function updateScheme(id, { name, scheme }) {
  const schemes = loadAllSchemes()
  const idx = schemes.findIndex(s => s.id === id)
  if (idx === -1) return null
  if (name != null) schemes[idx].name = name
  if (scheme != null) schemes[idx].scheme = scheme
  saveAllSchemes(schemes)
  return schemes[idx]
}

function deleteScheme(id) {
  let schemes = loadAllSchemes()
  schemes = schemes.filter(s => s.id !== id)
  saveAllSchemes(schemes)
  const active = getActiveSchemeId()
  if (active === id) {
    if (schemes.length) setActiveSchemeId(schemes[0].id)
    else localStorage.removeItem(STORAGE_KEY_ACTIVE)
  }
  return true
}

function setActiveSchemeId(id) {
  try {
    localStorage.setItem(STORAGE_KEY_ACTIVE, String(id))
    return true
  } catch (err) {
    console.error('Fehler beim Setzen des aktiven Schemas', err)
    return false
  }
}

function getActiveSchemeId() {
  return localStorage.getItem(STORAGE_KEY_ACTIVE) || null
}

function loadActiveByCourse() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY_ACTIVE_BY_COURSE)
    return raw ? JSON.parse(raw) : {}
  } catch (e) {
    console.error('Fehler beim Laden activeSchemeByCourse', e)
    return {}
  }
}

function saveActiveByCourse(map) {
  try {
    localStorage.setItem(STORAGE_KEY_ACTIVE_BY_COURSE, JSON.stringify(map || {}))
  } catch (e) {
    console.error('Fehler beim Speichern activeSchemeByCourse', e)
  }
}

function setActiveSchemeIdForCourse(courseId, schemeId) {
  const map = loadActiveByCourse()
  map[String(courseId)] = String(schemeId)
  saveActiveByCourse(map)
  return true
}

function getActiveSchemeIdForCourse(courseId) {
  const map = loadActiveByCourse()
  return map[String(courseId)] || null
}

function getActiveSchemeForCourse(courseId) {
  const id = getActiveSchemeIdForCourse(courseId)
  if (!id) return getActiveScheme() // fallback: global aktiv (oder default)
  return getSchemeById(id) || getActiveScheme()
}

function getSchemeById(id) {
  const schemes = loadAllSchemes()
  return schemes.find(s => s.id === id) || null
}

function getActiveScheme() {
  const id = getActiveSchemeId()
  const schemes = loadAllSchemes()
  if (!id) return schemes[0] || { id: 'default', name: 'Standard', scheme: DEFAULT_SCHEME }
  return schemes.find(s => s.id === id) || schemes[0] || { id: 'default', name: 'Standard', scheme: DEFAULT_SCHEME }
}

/**
 * Speichert das Schema lokal (localStorage).
 * @param {Object} scheme
 */
export function saveScheme(scheme) {
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(scheme))
  } catch (err) {
    console.error('Fehler beim Speichern des Grading-Schemas in localStorage', err)
  }
}

/**
 * Optionale Speicherung auf dem Backend (falls Endpunkt existiert).
 * Die Funktion versucht ein `POST /api/teacher-grading-scheme` (Beispiel)
 * aufzurufen. Falls der Endpunkt nicht existiert, wird der Fehler gefangen
 * und geloggt. Diese Funktion ist optional und sollte an das existierende
 * Backend angepasst werden, falls ein persistenter Server-Speicher gewünscht ist.
 * @param {Object} scheme
 */
export async function saveToBackend(scheme) {
  try {
    // Beispiel-URL; nur verwenden, wenn das Backend den Endpunkt unterstützt.
    await axios.post('/api/teacher-grading-scheme', scheme)
    return true
  } catch (err) {
    console.warn('Backend-Speicherung des Grading-Schemas fehlgeschlagen (vielleicht existiert kein Endpunkt).', err.message)
    return false
  }
}

/**
 * Prozent → Schulnote (Stufen wie im UI gezeigt)
 */
export function percentageToGrade(percentage, bands = DEFAULT_SCHEME.gradeBands) {
  const pct = Math.max(0, Math.min(100, Number(percentage)))
  const band = bands.find(b => pct >= b.min) || bands[bands.length - 1]
  return band ? band.grade : 5
}

/**
 * Konvertiert Punkte → Note über Prozentumrechnung
 * (passt zur Prozentanzeige im Lehrer-Dashboard)
 */
export function pointsToGrade(points, maxPoints) {
  if (!maxPoints || maxPoints <= 0) return 1
  const pct = Math.max(0, Math.min(100, (Number(points) / maxPoints) * 100))
  return percentageToGrade(pct)
}

/**
 * Validiert ein Schema-Objekt und gibt { valid: boolean, errors: [] } zurück.
 * - Prüft, ob mode gesetzt ist
 * - Bei 'group' prüft, ob Kategorien Prozentwerte haben, die insgesamt 100 ergeben
 */
export function validateScheme(scheme) {
  const errors = []
  if (!scheme || !scheme.mode) errors.push('Kein Modus gesetzt')

  const bands = Array.isArray(scheme?.gradeBands) ? scheme.gradeBands : []
  if (bands.length === 0) {
    errors.push('Keine Notengrenzen definiert')
  } else {
    bands.forEach((b, idx) => {
      const min = Number(b.min)
      const grade = Number(b.grade)
      if (!Number.isFinite(min) || min < 0 || min > 100) {
        errors.push(`Ungültige Prozentgrenze in Zeile ${idx + 1}`)
      }
      if (!Number.isFinite(grade) || grade <= 0) {
        errors.push(`Ungültige Note in Zeile ${idx + 1}`)
      }
    })
    const mins = bands.map(b => Number(b.min))
    const hasZero = mins.some(m => m === 0)
    if (!hasZero) errors.push('Notengrenzen müssen eine Untergrenze von 0% enthalten')
  }

  if (scheme.mode === 'group') {
    if (!Array.isArray(scheme.categories) || scheme.categories.length === 0) {
      errors.push('Keine Kategorien definiert')
    } else {
      const sum = scheme.categories.reduce((s, c) => s + (Number(c.percent) || 0), 0)
      if (Math.round(sum) !== 100) {
        errors.push(`Kategorien-Prozentsatz ergibt ${sum} (sollte 100 sein)`)
      }
    }
  }

  return { valid: errors.length === 0, errors }
}



export function computeFinalGrade(items = [], scheme = null, courseId = null) {
  // ✅ Schema automatisch bestimmen
  if (!scheme) {
    if (courseId != null) {
      // pro-Fach Schema (Multi-Schema Objekt -> .scheme)
      scheme = getActiveSchemeForCourse(courseId)?.scheme
    } else {
      // Fallback: altes single-schema
      scheme = loadScheme()
    }
  }

  // letzter Fallback, falls irgendwas null ist
  if (!scheme) scheme = { ...DEFAULT_SCHEME }

  // ===== AB HIER bleibt dein Original-Code unverändert =====

  // Normalisieren: Noten/Punkte → Prozent + Note, eigene MaxPoints möglich
  const normalized = items.map(it => {
    const max = it.maxPoints || scheme.maxPoints || DEFAULT_SCHEME.maxPoints
    const weight = it.gewichtung != null ? Number(it.gewichtung) : 1
    let pct = null
    let note = null

    if (scheme.scoreType === 'points') {
      const pts = it.points != null ? Number(it.points) : Number(it.note)
      pct = max > 0 ? Math.max(0, Math.min(100, (pts / max) * 100)) : null
      note = pct != null ? percentageToGrade(pct, scheme.gradeBands || DEFAULT_SCHEME.gradeBands) : null
    } else {
      note = it.note != null ? Number(it.note) : null
      pct = note != null ? 100 - ((note - 1) / 4) * 100 : null
    }

    return {
      ...it,
      points: it.points != null ? Number(it.points) : null,
      maxPoints: max,
      note,
      percentage: pct,
      gewichtung: weight,
      category: it.category || null
    }
  })

  if (scheme.mode === 'per-item') {
    let weightedSum = 0
    let weightSum = 0
    let pctWeighted = 0
    normalized.forEach(i => {
      const w = Number(i.gewichtung) || 1
      if (i.note != null) weightedSum += i.note * w
      if (i.percentage != null) pctWeighted += i.percentage * w
      weightSum += w
    })
    const final = weightSum > 0 ? weightedSum / weightSum : null
    const finalPct = weightSum > 0 ? pctWeighted / weightSum : null
    return {
      finalGrade: final != null ? Number(final.toFixed(2)) : null,
      finalPercent: finalPct != null ? Number(finalPct.toFixed(1)) : null,
      details: {
        mode: 'per-item',
        itemsCount: normalized.length,
        weightedSum,
        weightSum,
        percentAvg: finalPct
      }
    }
  }

  if (scheme.mode === 'group') {
    const categories = Array.isArray(scheme.categories) ? scheme.categories : []
    const catMap = {}
    categories.forEach(c => { catMap[c.key] = { ...c, percent: Number(c.percent) || 0 } })

    const itemsByCat = {}
    normalized.forEach(i => {
      const key = i.category || '__uncategorized__'
      if (!itemsByCat[key]) itemsByCat[key] = []
      itemsByCat[key].push(i)
    })

    const categoryResults = {}
    categories.forEach(cat => {
      const key = cat.key
      const itemsInCat = itemsByCat[key] || []
      if (itemsInCat.length === 0) {
        categoryResults[key] = { avg: null, count: 0 }
        return
      }
      let wSum = 0, wWeighted = 0
      itemsInCat.forEach(it => {
        const w = Number(it.gewichtung) || 1
        wWeighted += (Number(it.note) || 0) * w
        wSum += w
      })
      const avg = wSum > 0 ? wWeighted / wSum : null
      categoryResults[key] = { avg: avg != null ? Number(avg.toFixed(2)) : null, count: itemsInCat.length }
    })

    let final = 0
    let finalPct = 0
    let appliedPercentSum = 0
    categories.forEach(cat => {
      const key = cat.key
      const percent = Number(cat.percent) || 0
      const catRes = categoryResults[key]
      if (catRes && catRes.avg != null) {
        final += catRes.avg * (percent / 100)
        const approxPct = 100 - ((catRes.avg - 1) / 4) * 100
        finalPct += approxPct * (percent / 100)
        appliedPercentSum += percent
      }
    })

    return {
      finalGrade: appliedPercentSum > 0 ? Number(final.toFixed(2)) : null,
      finalPercent: appliedPercentSum > 0 ? Number(finalPct.toFixed(1)) : null,
      details: {
        mode: 'group',
        categories: categoryResults,
        appliedPercentSum
      }
    }
  }

  return { finalGrade: null, details: { error: 'Unknown grading mode' } }
}


/**
 * Hilfsfunktion: Beispiel-Usage
 *
 * const scheme = {
 *  mode: 'group',
 *  categories: [ { key: 'tests', name: 'Tests', percent: 50 }, { key: 'hw', name: 'Schularbeiten', percent: 50 } ]
 * }
 * saveScheme(scheme)
 * const items = [ { note: 2, gewichtung: 1, category: 'tests' }, { note: 1, gewichtung: 2, category: 'hw' } ]
 * const result = computeFinalGrade(items, scheme)
 */
export default {
  loadScheme,
  saveScheme,
  saveToBackend,
  validateScheme,
  computeFinalGrade,
  percentageToGrade,
  pointsToGrade,

  // multi-schema API
  loadAllSchemes,
  saveAllSchemes,
  createScheme,
  updateScheme,
  deleteScheme,
  setActiveSchemeId,
  getActiveSchemeId,
  getSchemeById,
  getActiveScheme,

  // ✅ per-course active scheme
  setActiveSchemeIdForCourse,
  getActiveSchemeIdForCourse,
  getActiveSchemeForCourse,
}

import axios from 'axios'

const STORAGE_KEY = 'gradingScheme'

/**
 * Standard-Schema (Fallback)
 * scoreType: 'grades' (1-5) oder 'points' (0-maxPoints)
 * maxPoints: maximale Punktzahl, falls scoreType='points' (z.B. 100)
 * gradeBands: Prozent → Note (passend zum UI: 60% ≈ Note 4)
 */
const DEFAULT_SCHEME = {
  mode: 'per-item', // 'per-item' | 'group'
  scoreType: 'grades', // 'grades' | 'points'
  maxPoints: 100, // nur relevant wenn scoreType='points'
  gradeBands: [
    { min: 92, grade: 1 },
    { min: 81, grade: 2 },
    { min: 65, grade: 3 },
    { min: 50, grade: 4 },
    { min: 0, grade: 5 }
  ],
  categories: [] // für group-mode
}

/**
 * Lädt das aktuell gespeicherte Schema (localStorage). Falls nichts
 * vorhanden, wird DEFAULT_SCHEME zurückgegeben.
 * @returns {Object}
 */
export function loadScheme() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    if (!raw) return { ...DEFAULT_SCHEME }
    return JSON.parse(raw)
  } catch (err) {
    console.error('Fehler beim Laden des Grading-Schemas aus localStorage', err)
    return { ...DEFAULT_SCHEME }
  }
}

/**
 * Multi-Schema support: store multiple named schemes and an active id
 */
const STORAGE_KEY_SCHEMES = 'gradingSchemes'
const STORAGE_KEY_ACTIVE = 'gradingActiveSchemeId'
const STORAGE_KEY_ACTIVE_BY_COURSE = 'gradingActiveSchemeByCourse'

function loadAllSchemes() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY_SCHEMES)
    if (!raw) {
      // migrate single scheme if present
      const single = localStorage.getItem(STORAGE_KEY)
      if (single) {
        const s = JSON.parse(single)
        const defaultScheme = { id: 'default', name: 'Standard', scheme: s }
        localStorage.setItem(STORAGE_KEY_SCHEMES, JSON.stringify([defaultScheme]))
        return [defaultScheme]
      }
      const def = { id: 'default', name: 'Standard', scheme: DEFAULT_SCHEME }
      localStorage.setItem(STORAGE_KEY_SCHEMES, JSON.stringify([def]))
      return [def]
    }
    const parsed = JSON.parse(raw)
    if (!Array.isArray(parsed)) throw new Error('gradingSchemes is not an array')

    // Deduplicate exact duplicates (same name + same scheme)
    const seen = new Set()
    const unique = []
    for (const s of parsed) {
      try {
        const key = (s.name || '') + '::' + JSON.stringify(s.scheme || {})
        if (!seen.has(key)) {
          seen.add(key)
          unique.push(s)
        }
      } catch (e) {
        // on stringify error, keep item
        unique.push(s)
      }
    }

    // If we only have many auto-created placeholders like 'Neues Schema',
    // collapse to single default to restore original behaviour.
    const onlyPlaceholders = unique.length > 1 && unique.every(u => {
      const n = (u.name || '').toLowerCase()
      return n === 'neues schema' || n.startsWith('neues schema') || n === 'neues'
    })
    if (onlyPlaceholders) {
      const def = { id: 'default', name: 'Standard', scheme: DEFAULT_SCHEME }
      localStorage.setItem(STORAGE_KEY_SCHEMES, JSON.stringify([def]))
      localStorage.setItem(STORAGE_KEY_ACTIVE, 'default')
      return [def]
    }

    if (unique.length !== parsed.length) {
      // save deduped list to avoid re-rendering the same duplicates
      try { localStorage.setItem(STORAGE_KEY_SCHEMES, JSON.stringify(unique)) } catch (e) {}
    }

    return unique
  } catch (err) {
    console.error('Fehler beim Laden aller Schemas', err)
    // Repair: reset to single default schema to avoid corrupt state
    try {
      const def = { id: 'default', name: 'Standard', scheme: DEFAULT_SCHEME }
      localStorage.setItem(STORAGE_KEY_SCHEMES, JSON.stringify([def]))
      localStorage.setItem(STORAGE_KEY_ACTIVE, 'default')
    } catch (e) {
      console.error('Fehler beim Reparieren des gradingSchemes keys', e)
    }
    return [{ id: 'default', name: 'Standard', scheme: DEFAULT_SCHEME }]
  }
}

function saveAllSchemes(arr) {
  try {
    localStorage.setItem(STORAGE_KEY_SCHEMES, JSON.stringify(arr))
  } catch (err) {
    console.error('Fehler beim Speichern aller Schemas', err)
  }
}

function createScheme(name = 'Neues Schema', schemeObj = null) {
  const schemes = loadAllSchemes()
  const id = String(Date.now())
  const s = { id, name, scheme: schemeObj || { ...DEFAULT_SCHEME } }
  schemes.push(s)
  saveAllSchemes(schemes)
  return s
}

function updateScheme(id, { name, scheme }) {
  const schemes = loadAllSchemes()
  const idx = schemes.findIndex(s => s.id === id)
  if (idx === -1) return null
  if (name != null) schemes[idx].name = name
  if (scheme != null) schemes[idx].scheme = scheme
  saveAllSchemes(schemes)
  return schemes[idx]
}

function deleteScheme(id) {
  let schemes = loadAllSchemes()
  schemes = schemes.filter(s => s.id !== id)
  saveAllSchemes(schemes)
  const active = getActiveSchemeId()
  if (active === id) {
    if (schemes.length) setActiveSchemeId(schemes[0].id)
    else localStorage.removeItem(STORAGE_KEY_ACTIVE)
  }
  return true
}

function setActiveSchemeId(id) {
  try {
    localStorage.setItem(STORAGE_KEY_ACTIVE, String(id))
    return true
  } catch (err) {
    console.error('Fehler beim Setzen des aktiven Schemas', err)
    return false
  }
}

function getActiveSchemeId() {
  return localStorage.getItem(STORAGE_KEY_ACTIVE) || null
}

function loadActiveByCourse() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY_ACTIVE_BY_COURSE)
    return raw ? JSON.parse(raw) : {}
  } catch (e) {
    console.error('Fehler beim Laden activeSchemeByCourse', e)
    return {}
  }
}

function saveActiveByCourse(map) {
  try {
    localStorage.setItem(STORAGE_KEY_ACTIVE_BY_COURSE, JSON.stringify(map || {}))
  } catch (e) {
    console.error('Fehler beim Speichern activeSchemeByCourse', e)
  }
}

function setActiveSchemeIdForCourse(courseId, schemeId) {
  const map = loadActiveByCourse()
  map[String(courseId)] = String(schemeId)
  saveActiveByCourse(map)
  return true
}

function getActiveSchemeIdForCourse(courseId) {
  const map = loadActiveByCourse()
  return map[String(courseId)] || null
}

function getActiveSchemeForCourse(courseId) {
  const id = getActiveSchemeIdForCourse(courseId)
  if (!id) return getActiveScheme() // fallback: global aktiv (oder default)
  return getSchemeById(id) || getActiveScheme()
}

function getSchemeById(id) {
  const schemes = loadAllSchemes()
  return schemes.find(s => s.id === id) || null
}

function getActiveScheme() {
  const id = getActiveSchemeId()
  const schemes = loadAllSchemes()
  if (!id) return schemes[0] || { id: 'default', name: 'Standard', scheme: DEFAULT_SCHEME }
  return schemes.find(s => s.id === id) || schemes[0] || { id: 'default', name: 'Standard', scheme: DEFAULT_SCHEME }
}

/**
 * Speichert das Schema lokal (localStorage).
 * @param {Object} scheme
 */
export function saveScheme(scheme) {
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(scheme))
  } catch (err) {
    console.error('Fehler beim Speichern des Grading-Schemas in localStorage', err)
  }
}

/**
 * Optionale Speicherung auf dem Backend (falls Endpunkt existiert).
 * Die Funktion versucht ein `POST /api/teacher-grading-scheme` (Beispiel)
 * aufzurufen. Falls der Endpunkt nicht existiert, wird der Fehler gefangen
 * und geloggt. Diese Funktion ist optional und sollte an das existierende
 * Backend angepasst werden, falls ein persistenter Server-Speicher gewünscht ist.
 * @param {Object} scheme
 */
export async function saveToBackend(scheme) {
  try {
    // Beispiel-URL; nur verwenden, wenn das Backend den Endpunkt unterstützt.
    await axios.post('/api/teacher-grading-scheme', scheme)
    return true
  } catch (err) {
    console.warn('Backend-Speicherung des Grading-Schemas fehlgeschlagen (vielleicht existiert kein Endpunkt).', err.message)
    return false
  }
}

/**
 * Prozent → Schulnote (Stufen wie im UI gezeigt)
 */
export function percentageToGrade(percentage, bands = DEFAULT_SCHEME.gradeBands) {
  const pct = Math.max(0, Math.min(100, Number(percentage)))
  const band = bands.find(b => pct >= b.min) || bands[bands.length - 1]
  return band ? band.grade : 5
}

/**
 * Konvertiert Punkte → Note über Prozentumrechnung
 * (passt zur Prozentanzeige im Lehrer-Dashboard)
 */
export function pointsToGrade(points, maxPoints) {
  if (!maxPoints || maxPoints <= 0) return 1
  const pct = Math.max(0, Math.min(100, (Number(points) / maxPoints) * 100))
  return percentageToGrade(pct)
}

/**
 * Validiert ein Schema-Objekt und gibt { valid: boolean, errors: [] } zurück.
 * - Prüft, ob mode gesetzt ist
 * - Bei 'group' prüft, ob Kategorien Prozentwerte haben, die insgesamt 100 ergeben
 */
export function validateScheme(scheme) {
  const errors = []
  if (!scheme || !scheme.mode) errors.push('Kein Modus gesetzt')

  if (scheme.mode === 'group') {
    if (!Array.isArray(scheme.categories) || scheme.categories.length === 0) {
      errors.push('Keine Kategorien definiert')
    } else {
      const sum = scheme.categories.reduce((s, c) => s + (Number(c.percent) || 0), 0)
      if (Math.round(sum) !== 100) {
        errors.push(`Kategorien-Prozentsatz ergibt ${sum} (sollte 100 sein)`)
      }
    }
  }

  return { valid: errors.length === 0, errors }
}



export function computeFinalGrade(items = [], scheme = null, courseId = null) {
  // ✅ Schema automatisch bestimmen
  if (!scheme) {
    if (courseId != null) {
      // pro-Fach Schema (Multi-Schema Objekt -> .scheme)
      scheme = getActiveSchemeForCourse(courseId)?.scheme
    } else {
      // Fallback: altes single-schema
      scheme = loadScheme()
    }
  }

  // letzter Fallback, falls irgendwas null ist
  if (!scheme) scheme = { ...DEFAULT_SCHEME }

  // ===== AB HIER bleibt dein Original-Code unverändert =====

  // Normalisieren: Noten/Punkte → Prozent + Note, eigene MaxPoints möglich
  const normalized = items.map(it => {
    const max = it.maxPoints || scheme.maxPoints || DEFAULT_SCHEME.maxPoints
    const weight = it.gewichtung != null ? Number(it.gewichtung) : 1
    let pct = null
    let note = null

    if (scheme.scoreType === 'points') {
      const pts = it.points != null ? Number(it.points) : Number(it.note)
      pct = max > 0 ? Math.max(0, Math.min(100, (pts / max) * 100)) : null
      note = pct != null ? percentageToGrade(pct, scheme.gradeBands || DEFAULT_SCHEME.gradeBands) : null
    } else {
      note = it.note != null ? Number(it.note) : null
      pct = note != null ? 100 - ((note - 1) / 4) * 100 : null
    }

    return {
      ...it,
      points: it.points != null ? Number(it.points) : null,
      maxPoints: max,
      note,
      percentage: pct,
      gewichtung: weight,
      category: it.category || null
    }
  })

  if (scheme.mode === 'per-item') {
    let weightedSum = 0
    let weightSum = 0
    let pctWeighted = 0
    normalized.forEach(i => {
      const w = Number(i.gewichtung) || 1
      if (i.note != null) weightedSum += i.note * w
      if (i.percentage != null) pctWeighted += i.percentage * w
      weightSum += w
    })
    const final = weightSum > 0 ? weightedSum / weightSum : null
    const finalPct = weightSum > 0 ? pctWeighted / weightSum : null
    return {
      finalGrade: final != null ? Number(final.toFixed(2)) : null,
      finalPercent: finalPct != null ? Number(finalPct.toFixed(1)) : null,
      details: {
        mode: 'per-item',
        itemsCount: normalized.length,
        weightedSum,
        weightSum,
        percentAvg: finalPct
      }
    }
  }

  if (scheme.mode === 'group') {
    const categories = Array.isArray(scheme.categories) ? scheme.categories : []
    const catMap = {}
    categories.forEach(c => { catMap[c.key] = { ...c, percent: Number(c.percent) || 0 } })

    const itemsByCat = {}
    normalized.forEach(i => {
      const key = i.category || '__uncategorized__'
      if (!itemsByCat[key]) itemsByCat[key] = []
      itemsByCat[key].push(i)
    })

    const categoryResults = {}
    categories.forEach(cat => {
      const key = cat.key
      const itemsInCat = itemsByCat[key] || []
      if (itemsInCat.length === 0) {
        categoryResults[key] = { avg: null, count: 0 }
        return
      }
      let wSum = 0, wWeighted = 0
      itemsInCat.forEach(it => {
        const w = Number(it.gewichtung) || 1
        wWeighted += (Number(it.note) || 0) * w
        wSum += w
      })
      const avg = wSum > 0 ? wWeighted / wSum : null
      categoryResults[key] = { avg: avg != null ? Number(avg.toFixed(2)) : null, count: itemsInCat.length }
    })

    let final = 0
    let finalPct = 0
    let appliedPercentSum = 0
    categories.forEach(cat => {
      const key = cat.key
      const percent = Number(cat.percent) || 0
      const catRes = categoryResults[key]
      if (catRes && catRes.avg != null) {
        final += catRes.avg * (percent / 100)
        const approxPct = 100 - ((catRes.avg - 1) / 4) * 100
        finalPct += approxPct * (percent / 100)
        appliedPercentSum += percent
      }
    })

    return {
      finalGrade: appliedPercentSum > 0 ? Number(final.toFixed(2)) : null,
      finalPercent: appliedPercentSum > 0 ? Number(finalPct.toFixed(1)) : null,
      details: {
        mode: 'group',
        categories: categoryResults,
        appliedPercentSum
      }
    }
  }

  return { finalGrade: null, details: { error: 'Unknown grading mode' } }
}


/**
 * Hilfsfunktion: Beispiel-Usage
 *
 * const scheme = {
 *  mode: 'group',
 *  categories: [ { key: 'tests', name: 'Tests', percent: 50 }, { key: 'hw', name: 'Schularbeiten', percent: 50 } ]
 * }
 * saveScheme(scheme)
 * const items = [ { note: 2, gewichtung: 1, category: 'tests' }, { note: 1, gewichtung: 2, category: 'hw' } ]
 * const result = computeFinalGrade(items, scheme)
 */
export default {
  loadScheme,
  saveScheme,
  saveToBackend,
  validateScheme,
  computeFinalGrade,
  percentageToGrade,
  pointsToGrade,

  // multi-schema API
  loadAllSchemes,
  saveAllSchemes,
  createScheme,
  updateScheme,
  deleteScheme,
  setActiveSchemeId,
  getActiveSchemeId,
  getSchemeById,
  getActiveScheme,

  // ✅ per-course active scheme
  setActiveSchemeIdForCourse,
  getActiveSchemeIdForCourse,
  getActiveSchemeForCourse,
}
