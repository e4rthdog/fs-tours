<template>
  <q-layout view="lHh Lpr lFf">
    <q-header class="bg-dark text-white">
      <!-- Desktop layout -->
      <q-toolbar class="gt-sm row items-center">
        <HeaderElements show-logo-title />
        <q-space />
        <HeaderElements show-select />
        <HeaderElements show-buttons />
        <HeaderElements
          show-admin-actions
          admin-actions-class="q-ml-lg row items-center tour-actions-group"
        />
      </q-toolbar>

      <!-- Mobile layout -->
      <div class="lt-md">
        <!-- Row 1: Logo and Title -->
        <q-toolbar class="q-py-xs">
          <HeaderElements show-logo-title />
        </q-toolbar>

        <!-- Row 2: Select and buttons -->
        <q-toolbar class="q-py-xs">
          <HeaderElements show-select select-style="min-width: 200px; flex-grow: 1" />
          <HeaderElements show-buttons />
        </q-toolbar>

        <!-- Row 3: Admin actions -->
        <q-toolbar v-if="store.isAdmin" class="q-py-xs">
          <HeaderElements
            show-admin-actions
            admin-actions-class="row items-center full-width"
            is-mobile
          />
        </q-toolbar>
      </div>
    </q-header>

    <q-page-container>
      <router-view />
    </q-page-container>

    <q-footer class="bg-dark text-white">
      <div class="q-pa-sm q-gutter-sm row items-center justify-between">
        <div>FS Tours &copy; {{ new Date().getFullYear() }}</div>
        <div class="text-caption">Map last updated: {{ lastUpdated }}</div>
      </div>
    </q-footer>

    <!-- Add Leg Dialog -->
    <q-dialog v-model="addLegDialog.show" persistent dark>
      <q-card flat style="min-width: 400px; max-width: 90vw">
        <LegForm
          v-model="addLegDialog.form"
          :loading="store.loading"
          @submit="submitAddLeg"
          @cancel="addLegDialog.show = false"
        />
      </q-card>
    </q-dialog>

    <!-- Edit Tour Dialog -->
    <q-dialog v-model="editTourDialog.show" persistent dark>
      <q-card flat style="min-width: 400px; max-width: 90vw">
        <TourForm
          v-model="editTourDialog.form"
          :loading="store.loading"
          @submit="submitEditTour"
          @cancel="editTourDialog.show = false"
        />
      </q-card>
    </q-dialog>

    <!-- Add Tour Dialog -->
    <q-dialog v-model="addTourDialog.show" persistent dark>
      <q-card flat style="min-width: 400px; max-width: 90vw">
        <AddTourForm
          v-model="addTourDialog.form"
          :loading="store.loading"
          @submit="submitAddTour"
          @cancel="addTourDialog.show = false"
        />
      </q-card>
    </q-dialog>
  </q-layout>
</template>

<script setup>
import { ref, onMounted, computed, reactive, provide } from 'vue'
import { useFsToursStore } from 'stores/fstours'
import { useQuasar } from 'quasar'
import { useRoute, useRouter } from 'vue-router'
import LegForm from 'components/LegForm.vue'
import TourForm from 'components/TourForm.vue'
import AddTourForm from 'components/AddTourForm.vue'
import HeaderElements from 'components/HeaderElements.vue'

const store = useFsToursStore()
const $q = useQuasar()
const route = useRoute()
const router = useRouter()
const tourOptions = computed(() => {
  return store.tours.map((tour) => ({
    label: tour.tour_description,
    value: tour.tour_id,
  }))
})

const lastUpdated = ref('--:--:--Z')
const addLegDialog = reactive({
  show: false,
  form: {
    tour_id: '',
    origin: '',
    destination: '',
    aircraft: '',
    route: '',
    comments: '',
    link1: '',
    link2: '',
    link3: '',
    flight_date: '',
  },
})

const editTourDialog = reactive({
  show: false,
  form: {
    tour_id: '',
    tour_description: '',
  },
})

const addTourDialog = reactive({
  show: false,
  form: {
    tour_id: '',
    tour_description: '',
  },
})

async function onTourSelected(tourId) {
  if (tourId) {
    try {
      store.setSelectedTour(tourId)

      // Update URL without causing a full page reload
      if (route.params.tourId !== tourId) {
        router.push({ path: `/tour/${tourId}` })
      }

      await store.fetchTourLegs(tourId)
      updateLastUpdated()
    } catch (err) {
      $q.notify({
        type: 'negative',
        message: `Error loading tour legs: ${err.message}`,
      })
    }
  } else {
    // Handle clearing the selection
    store.setSelectedTour(null)
    store.clearLegs()

    // Navigate back to home when no tour is selected
    if (route.path !== '/') {
      router.push('/')
    }
  }
}

function updateLastUpdated() {
  const now = new Date()
  const hours = now.getUTCHours().toString().padStart(2, '0')
  const minutes = now.getUTCMinutes().toString().padStart(2, '0')
  const seconds = now.getUTCSeconds().toString().padStart(2, '0')
  lastUpdated.value = `${hours}:${minutes}:${seconds}Z`
}

function toggleAdmin() {
  if (store.isAdmin) {
    // Logout admin
    store.logoutAdmin()
    $q.notify({
      type: 'positive',
      message: 'Logged out of admin mode',
      position: 'top',
      timeout: 2000,
    })
  } else {
    // Show password prompt
    $q.dialog({
      title: 'Admin Authentication',
      message: 'Enter admin password:',
      prompt: {
        model: '',
        type: 'password',
      },
      cancel: true,
      persistent: true,
      dark: true,
    }).onOk(async (password) => {
      try {
        await store.authenticateAdmin(password)
        $q.notify({
          type: 'positive',
          message: 'Admin authentication successful',
          position: 'top',
          timeout: 2000,
        })
      } catch (err) {
        $q.notify({
          type: 'negative',
          message: err.message || 'Authentication failed',
          position: 'top',
          timeout: 3000,
        })
      }
    })
  }
}

async function refreshData() {
  try {
    await store.fetchTours()
    updateLastUpdated()
    $q.notify({
      type: 'positive',
      message: `Tours refreshed successfully`,
    })
  } catch (err) {
    $q.notify({
      type: 'negative',
      message: `Error refreshing tours: ${err.message}`,
    })
  }
}

const openAddLegDialog = () => {
  // Reset form with current tour ID
  addLegDialog.form = {
    tour_id: store.selectedTour || '',
    origin: '',
    destination: '',
    aircraft: '',
    route: '',
    comments: '',
    link1: '',
    link2: '',
    link3: '',
    flight_date: '',
  }
  addLegDialog.show = true
}

const submitAddLeg = async () => {
  try {
    store.loading = true
    await store.addLeg(addLegDialog.form)
    addLegDialog.show = false

    // Refresh tour legs after adding
    if (store.selectedTour) {
      await store.fetchTourLegs(store.selectedTour)
    }

    $q.notify({
      type: 'positive',
      message: 'Leg added successfully',
      position: 'top',
      timeout: 2000,
    })
  } catch (err) {
    $q.notify({
      type: 'negative',
      message: err.message || 'Failed to add leg',
      position: 'top',
      timeout: 3000,
    })
  } finally {
    store.loading = false
  }
}

const openEditTourDialog = () => {
  const selectedTourData = store.tours.find((tour) => tour.tour_id === store.selectedTour)
  if (selectedTourData) {
    editTourDialog.form = {
      tour_id: selectedTourData.tour_id,
      tour_description: selectedTourData.tour_description,
    }
    editTourDialog.show = true
  }
}

const submitEditTour = async () => {
  try {
    store.loading = true
    await store.updateTour(editTourDialog.form)
    editTourDialog.show = false

    // Refresh tours after updating
    await store.fetchTours()

    $q.notify({
      type: 'positive',
      message: 'Tour updated successfully',
      position: 'top',
      timeout: 2000,
    })
  } catch (err) {
    $q.notify({
      type: 'negative',
      message: err.message || 'Failed to update tour',
      position: 'top',
      timeout: 3000,
    })
  } finally {
    store.loading = false
  }
}

const openAddTourDialog = () => {
  addTourDialog.form = {
    tour_id: '',
    tour_description: '',
  }
  addTourDialog.show = true
}

const submitAddTour = async () => {
  try {
    store.loading = true
    await store.addTour(addTourDialog.form)
    addTourDialog.show = false

    // Refresh tours after adding
    await store.fetchTours()

    $q.notify({
      type: 'positive',
      message: 'Tour added successfully',
      position: 'top',
      timeout: 2000,
    })
  } catch (err) {
    $q.notify({
      type: 'negative',
      message: err.message || 'Failed to add tour',
      position: 'top',
      timeout: 3000,
    })
  } finally {
    store.loading = false
  }
}

const confirmDeleteTour = () => {
  if (!store.selectedTour) return

  const selectedTourData = store.tours.find((tour) => tour.tour_id === store.selectedTour)
  const tourDescription = selectedTourData ? selectedTourData.tour_description : store.selectedTour

  $q.dialog({
    flat: true,
    title: 'Confirm Tour Deletion',
    message: `Are you sure you want to delete the tour "<b>${tourDescription}</b>"?<br><br>This action will permanently delete:<br>&bull; The tour itself<br>&bull; ALL flight legs associated with this tour<br><br><b>This action cannot be undone!</b>`,
    html: true,
    cancel: {
      label: 'Cancel',
      flat: true,
      color: 'white',
    },
    ok: {
      label: 'Delete Tour',
      color: 'negative',
    },
    persistent: true,
    dark: true,
  }).onOk(async () => {
    try {
      store.loading = true
      await store.deleteTour(store.selectedTour)

      // Clear selected tour and legs after deletion
      store.setSelectedTour(null)
      store.clearLegs()

      // Refresh tours list
      await store.fetchTours()

      $q.notify({
        type: 'positive',
        message: 'Tour and all associated legs deleted successfully',
        position: 'top',
        timeout: 3000,
      })
    } catch (err) {
      $q.notify({
        type: 'negative',
        message: err.message || 'Failed to delete tour',
        position: 'top',
        timeout: 3000,
      })
    } finally {
      store.loading = false
    }
  })
}

function shareCurrentTour() {
  if (!store.selectedTour) return

  // Copy current URL directly - it already contains the correct tour
  navigator.clipboard
    .writeText(window.location.href)
    .then(() => {
      $q.notify({
        type: 'positive',
        message: 'Tour URL copied to clipboard!',
        position: 'top',
        timeout: 2000,
      })
    })
    .catch(() => {
      // Fallback: show the URL in a dialog
      $q.dialog({
        title: 'Share Tour',
        message: 'Copy this URL to share the current tour:',
        prompt: {
          model: window.location.href,
          readonly: true,
        },
        ok: 'Close',
        dark: true,
      })
    })
}

onMounted(async () => {
  try {
    await store.fetchTours()
    updateLastUpdated()

    // Check if there's a tour parameter in the URL
    const tourIdFromUrl = route.params.tourId
    if (tourIdFromUrl) {
      // Wait for tours to be loaded, then check if the tour exists
      const tourExists = store.tours.some((tour) => tour.tour_id === tourIdFromUrl)
      if (tourExists) {
        // Auto-select the tour from URL parameter
        store.setSelectedTour(tourIdFromUrl)
        await store.fetchTourLegs(tourIdFromUrl)
      } else {
        // Tour doesn't exist, redirect to home and show error
        router.push('/')
        $q.notify({
          type: 'negative',
          message: `Tour "${tourIdFromUrl}" not found`,
          position: 'top',
          timeout: 3000,
        })
      }
    }
  } catch (err) {
    $q.notify({
      type: 'negative',
      message: `Error loading tours: ${err.message}`,
    })
  }
})

// Provide methods to child components
provide('headerMethods', {
  store,
  tourOptions,
  onTourSelected,
  refreshData,
  shareCurrentTour,
  toggleAdmin,
  openAddTourDialog,
  openEditTourDialog,
  confirmDeleteTour,
  openAddLegDialog,
})
</script>

<style scoped></style>
