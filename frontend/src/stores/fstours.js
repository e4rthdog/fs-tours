import { defineStore, acceptHMRUpdate } from 'pinia'
import { ref } from 'vue'

export const useFsToursStore = defineStore('fstours', () => {
  const legs = ref([])
  const tours = ref([])
  const selectedTour = ref(null)
  const loading = ref(false)
  const error = ref(null)

  async function fetchLegs() {
    loading.value = true
    error.value = null
    try {
      const res = await fetch('http://fs-tours-api.ddev.site/legs')
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
      const res = await fetch(`http://fs-tours-api.ddev.site/tours/${tourId}/legs`)
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
      const res = await fetch('http://fs-tours-api.ddev.site/tours')
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

  return {
    legs,
    tours,
    selectedTour,
    loading,
    error,
    fetchLegs,
    fetchTourLegs,
    fetchTours,
    setSelectedTour,
    clearLegs,
  }
})

if (import.meta.hot) {
  import.meta.hot.accept(acceptHMRUpdate(useFsToursStore, import.meta.hot))
}
