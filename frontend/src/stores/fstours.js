import { defineStore, acceptHMRUpdate } from 'pinia'
import { ref } from 'vue'
import { config } from '../config/config.js'

const API_BASE_URL = config.apiBaseUrl

export const useFsToursStore = defineStore('fstours', () => {
  const legs = ref([])
  const tours = ref([])
  const selectedTour = ref(null)
  const loading = ref(false)
  const error = ref(null)
  const isAdmin = ref(false)
  const adminToken = ref('')

  async function fetchLegs() {
    loading.value = true
    error.value = null
    try {
      const res = await fetch(`${API_BASE_URL}/legs`)
      if (!res.ok) throw new Error('Failed to fetch legs')
      legs.value = await res.json()
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  async function fetchTourLegs(tourId) {
    if (!tourId) return

    loading.value = true
    error.value = null
    try {
      const res = await fetch(`${API_BASE_URL}/tours/${tourId}/legs`)
      if (!res.ok) throw new Error(`Failed to fetch legs for tour ${tourId}`)
      const data = await res.json()
      legs.value = data
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  async function fetchTours() {
    loading.value = true
    error.value = null
    try {
      const res = await fetch(`${API_BASE_URL}/tours`)
      if (!res.ok) throw new Error('Failed to fetch tours')
      tours.value = await res.json()
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  function setSelectedTour(tourId) {
    selectedTour.value = tourId
  }

  function clearLegs() {
    legs.value = []
  }

  async function deleteLeg(legId) {
    loading.value = true
    error.value = null
    try {
      const res = await makeAdminRequest(`${API_BASE_URL}/legs/${legId}`, {
        method: 'DELETE',
      })

      if (!res.ok) {
        const errorData = await res.json().catch(() => ({ message: 'Failed to delete leg' }))
        throw new Error(errorData.message || 'Failed to delete leg')
      }

      const result = await res.json()
      return result
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  async function updateLeg(leg) {
    loading.value = true
    error.value = null
    try {
      const res = await makeAdminRequest(`${API_BASE_URL}/legs/${leg.id}`, {
        method: 'PUT',
        body: JSON.stringify({
          id: leg.id,
          tour_id: leg.tour_id,
          origin: leg.origin,
          destination: leg.destination,
          aircraft: leg.aircraft,
          route: leg.route,
          comments: leg.comments,
          link1: leg.link1,
          link2: leg.link2,
          link3: leg.link3,
          flight_date: leg.flight_date,
        }),
      })
      if (!res.ok) {
        const errorData = await res.json().catch(() => ({ message: 'Failed to update leg' }))
        throw new Error(errorData.message || 'Failed to update leg')
      }
      const result = await res.json()
      return result
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  async function addLeg(leg) {
    loading.value = true
    error.value = null
    try {
      const res = await makeAdminRequest(`${API_BASE_URL}/legs`, {
        method: 'POST',
        body: JSON.stringify({
          tour_id: leg.tour_id,
          origin: leg.origin,
          destination: leg.destination,
          aircraft: leg.aircraft,
          route: leg.route,
          comments: leg.comments,
          link1: leg.link1,
          link2: leg.link2,
          link3: leg.link3,
          flight_date: leg.flight_date,
        }),
      })

      if (!res.ok) {
        const errorData = await res.json().catch(() => ({ message: 'Failed to add leg' }))
        throw new Error(errorData.message || 'Failed to add leg')
      }

      const result = await res.json()
      return result
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateTour = async (tour) => {
    loading.value = true
    error.value = null

    try {
      const res = await makeAdminRequest(`${API_BASE_URL}/tours/${tour.tour_id}`, {
        method: 'PUT',
        body: JSON.stringify({
          tour_description: tour.tour_description,
        }),
      })

      if (!res.ok) {
        const errorData = await res.json().catch(() => ({ message: 'Failed to update tour' }))
        throw new Error(errorData.message || 'Failed to update tour')
      }

      return await res.json()
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  const addTour = async (tour) => {
    loading.value = true
    error.value = null

    try {
      const res = await makeAdminRequest(`${API_BASE_URL}/tours`, {
        method: 'POST',
        body: JSON.stringify({
          tour_id: tour.tour_id,
          tour_description: tour.tour_description,
        }),
      })

      if (!res.ok) {
        const errorData = await res.json().catch(() => ({ message: 'Failed to create tour' }))
        throw new Error(errorData.message || 'Failed to create tour')
      }

      return await res.json()
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  const deleteTour = async (tourId) => {
    loading.value = true
    error.value = null

    try {
      const res = await makeAdminRequest(`${API_BASE_URL}/tours/${tourId}`, {
        method: 'DELETE',
      })

      if (!res.ok) {
        const errorData = await res.json().catch(() => ({ message: 'Failed to delete tour' }))
        throw new Error(errorData.message || 'Failed to delete tour')
      }

      return await res.json()
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  async function fetchSimbriefData() {
    const username = config.simbriefUsername

    try {
      const response = await fetch(
        `https://www.simbrief.com/api/xml.fetcher.php?username=${username}&json=1`,
      )

      if (!response.ok) {
        throw new Error('Failed to fetch SimBrief data')
      }

      const data = await response.json()

      // Check if the response contains flight plan data
      if (!data.origin || !data.destination) {
        throw new Error('No valid flight plan found for this username')
      }

      // Build comments with distance and altitude info
      const distance = data.general?.route_distance ? `${data.general.route_distance} nm` : ''
      const altitude = data.general?.initial_altitude
        ? `FL${Math.round(data.general.initial_altitude / 100)}`
        : ''
      const comments = [distance, altitude].filter(Boolean).join(', ')

      return {
        origin: data.origin.icao_code || '',
        destination: data.destination.icao_code || '',
        aircraft: data.aircraft.icao_code || '',
        route: data.general?.route || '',
        comments: comments,
        link: data.params?.request_id ? data.files?.directory + data.files.pdf?.link : '',
      }
    } catch (error) {
      throw new Error(`SimBrief import failed: ${error.message}`)
    }
  }

  async function authenticateAdmin(password) {
    loading.value = true
    error.value = null
    try {
      // Store the password as token temporarily
      const tempToken = password

      // Test authentication by making a request with the token
      const res = await fetch(`${API_BASE_URL}/tours`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          Authorization: `Bearer ${tempToken}`,
        },
        body: JSON.stringify({
          tour_id: '__test__',
          tour_description: 'test',
        }),
        mode: 'cors',
      })

      if (res.status === 401) {
        throw new Error('Invalid password')
      }

      // If we get here, authentication was successful (even if tour creation failed for other reasons)
      // Delete the test tour if it was created
      if (res.ok) {
        await fetch(`${API_BASE_URL}/tours/__test__`, {
          method: 'DELETE',
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            Authorization: `Bearer ${tempToken}`,
          },
          mode: 'cors',
        })
      }

      isAdmin.value = true
      adminToken.value = password
      return { success: true, message: 'Authentication successful' }
    } catch (err) {
      error.value = err.message
      isAdmin.value = false
      adminToken.value = ''
      throw err
    } finally {
      loading.value = false
    }
  }

  function logoutAdmin() {
    isAdmin.value = false
    adminToken.value = ''
  }

  // Helper function to make admin requests with authorization
  function makeAdminRequest(url, options = {}) {
    return fetch(url, {
      ...options,
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        Authorization: `Bearer ${adminToken.value}`,
        ...options.headers,
      },
      mode: 'cors',
    })
  }

  return {
    legs,
    tours,
    selectedTour,
    loading,
    error,
    isAdmin,
    fetchLegs,
    fetchTourLegs,
    fetchTours,
    setSelectedTour,
    clearLegs,
    deleteLeg,
    updateLeg,
    addLeg,
    updateTour,
    addTour,
    deleteTour,
    fetchSimbriefData,
    authenticateAdmin,
    logoutAdmin,
  }
})

if (import.meta.hot) {
  import.meta.hot.accept(acceptHMRUpdate(useFsToursStore, import.meta.hot))
}
