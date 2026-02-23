import { apiClient } from './apiClient'

export async function getMyCourses() {
  const res = await apiClient.get('/lehrer/faecher')
  return res.data || []
}

export async function getCourseDetail(kursId) {
  const res = await apiClient.get(`/lehrer/faecher/${kursId}`)
  return res.data
}

export async function deleteCourse(kursId) {
  const res = await apiClient.delete(`/lehrer/faecher/${kursId}`)
  return res.data
}

export async function getCourseOverview(kursId) {
  const res = await apiClient.get(`/lehrer/faecher/${kursId}/uebersicht`)
  return res.data
}

export async function getCourseStudents(kursId) {
  const res = await apiClient.get(`/lehrer/faecher/${kursId}/schueler`)
  return res.data || []
}

export async function getGradingTypes(kursId) {
  const res = await apiClient.get(`/lehrer/faecher/${kursId}/benotungsarten`)
  return res.data || []
}

export async function getAssessmentsForCourse(kursId, params = {}) {
  const res = await apiClient.get(`/lehrer/faecher/${kursId}/leistungsfeststellungen`, { params })
  return res.data || []
}

export async function createAssessment(kursId, payload) {
  const res = await apiClient.post(`/lehrer/faecher/${kursId}/leistungsfeststellungen`, payload)
  return res.data
}

export async function deleteAssessment(id) {
  const res = await apiClient.delete(`/lehrer/leistungsfeststellungen/${id}`)
  return res.data
}

export async function getAssessmentDetail(id) {
  const res = await apiClient.get(`/lehrer/leistungsfeststellungen/${id}`)
  return res.data
}

export async function createStudentResult(assessmentId, payload) {
  const res = await apiClient.post(`/lehrer/leistungsfeststellungen/${assessmentId}/schuelerleistungen`, payload)
  return res.data
}

export async function getTeacherSettings() {
  const res = await apiClient.get('/lehrer/einstellungen')
  return res.data
}

export async function updateTeacherSettings(payload) {
  const res = await apiClient.put('/lehrer/einstellungen', payload)
  return res.data
}
