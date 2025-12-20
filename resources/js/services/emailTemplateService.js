import axios from 'axios'

export const emailTemplateService = {
  async getTemplates(params = {}) {
    const response = await axios.get('/api/email-templates', { params })
    return response.data
  },

  async getTemplate(id) {
    const response = await axios.get(`/api/email-templates/${id}`)
    return response.data
  },

  async createTemplate(data) {
    const response = await axios.post('/api/email-templates', data)
    return response.data
  },

  async updateTemplate(id, data) {
    const response = await axios.put(`/api/email-templates/${id}`, data)
    return response.data
  },

  async deleteTemplate(id) {
    await axios.delete(`/api/email-templates/${id}`)
  },

  async duplicateTemplate(id) {
    const response = await axios.post(`/api/email-templates/${id}/duplicate`)
    return response.data
  },

  async previewTemplate(id) {
    const response = await axios.post(`/api/email-templates/${id}/preview`)
    return response.data
  },

  async sendTestEmail(id, emails) {
    const response = await axios.post(`/api/email-templates/${id}/send-test`, { emails })
    return response.data
  },

  async getVariables(id) {
    const response = await axios.get(`/api/email-templates/${id}/variables`)
    return response.data
  }
}
