import { defineStore, acceptHMRUpdate } from 'pinia'
import { ref } from 'vue'

export const useFsToursStore = defineStore('fstours', () => {
  const legs = ref([])
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
    } finally {
      loading.value = false
    }
  }

  return { legs, loading, error, fetchLegs }
})

if (import.meta.hot) {
  import.meta.hot.accept(acceptHMRUpdate(useFsToursStore, import.meta.hot))
}
