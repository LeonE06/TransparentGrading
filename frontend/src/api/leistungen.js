import axios from 'axios'

const isDev = import.meta.env.DEV
const apiBase = import.meta.env.VITE_API_URL || ''
const apiPrefix = isDev ? '' : `${apiBase}/api`

export async function getLeistungen() {
  return axios.get(`${apiPrefix}/leistungen`)
}

export async function addLeistung(payload) {
  return axios.post(`${apiPrefix}/leistungen`, payload)
}

export async function deleteLeistung(id) {
  return axios.delete(`${apiPrefix}/leistungen/${id}`)
}

/**
 * Hole alle Schüler einer Klasse (für die Auswahl bei Leistungserfassung)
 * @param {string} klasseId - ID der Klasse (optional, falls filtern nötig)
 */
export async function getStudents(klasseId = null) {
  try {
    const url = klasseId ? `${apiPrefix}/schueler?klasse=${klasseId}` : `${apiPrefix}/schueler`
    return axios.get(url)
  } catch (err) {
    console.error('Fehler beim Laden der Schüler', err)
    return { data: [] }
  }
}

