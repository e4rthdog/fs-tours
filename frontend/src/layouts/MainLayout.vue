<template>
  <q-layout view="lHh Lpr lFf">
    <q-header class="bg-dark text-white">
      <q-toolbar>
        <q-avatar size="32px" class="q-mr-md">
          <img src="https://i.imgur.com/4M34hi2.png" alt="Logo" />
        </q-avatar>
        <q-toolbar-title shrink class="q-mx-lg">FS Tours</q-toolbar-title>
        <q-select
          v-model="store.selectedTour"
          :options="tourOptions"
          option-value="value"
          option-label="label"
          label="Select Tour"
          dense
          outlined
          style="min-width: 200px"
          :loading="store.loading"
          @update:model-value="onTourSelected"
          emit-value
          map-options
        >
          <template v-slot:no-option>
            <q-item>
              <q-item-section class="text-grey">No tours found</q-item-section>
            </q-item>
          </template>
        </q-select>
        <q-btn
          flat
          round
          dense
          icon="refresh"
          class="q-ml-sm"
          :loading="store.loading"
          @click="refreshData"
          title="Refresh tours data"
        />
        <div class="q-ml-lg row items-center tour-actions-group">
          <span class="text-body1 q-mr-sm">Tour actions:</span>
          <q-btn-group flat>
            <q-btn flat round dense icon="add" title="Create Tour" @click="openAddTourDialog" />
            <q-btn
              flat
              round
              dense
              icon="edit"
              title="Edit Tour"
              :disable="!store.selectedTour"
              @click="openEditTourDialog"
            />
            <q-btn flat round dense icon="delete" title="Delete Tour" />
          </q-btn-group>
          <q-separator vertical spaced class="q-mx-md" />
          <q-btn
            class="q-px-md"
            color="primary"
            icon="add"
            label="Add Leg"
            dense
            :disable="!store.selectedTour"
            @click="openAddLegDialog"
          />
        </div>
      </q-toolbar>
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
      <q-card style="min-width: 400px; max-width: 90vw">
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
      <q-card style="min-width: 400px; max-width: 90vw">
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
      <q-card style="min-width: 400px; max-width: 90vw">
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
import { ref, onMounted, computed, reactive } from 'vue'
import { useFsToursStore } from 'stores/fstours'
import { useQuasar } from 'quasar'
import LegForm from 'components/LegForm.vue'
import TourForm from 'components/TourForm.vue'
import AddTourForm from 'components/AddTourForm.vue'

const store = useFsToursStore()
const $q = useQuasar()
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
  }
}

function updateLastUpdated() {
  const now = new Date()
  const hours = now.getUTCHours().toString().padStart(2, '0')
  const minutes = now.getUTCMinutes().toString().padStart(2, '0')
  const seconds = now.getUTCSeconds().toString().padStart(2, '0')
  lastUpdated.value = `${hours}:${minutes}:${seconds}Z`
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

onMounted(async () => {
  try {
    await store.fetchTours()
    updateLastUpdated()
  } catch (err) {
    $q.notify({
      type: 'negative',
      message: `Error loading tours: ${err.message}`,
    })
  }
})
</script>

<style scoped></style>
