<template>
  <q-layout view="lHh Lpr lFf">
    <q-header elevated class="bg-dark text-white">
      <q-toolbar>
        <q-toolbar-title>
          <q-avatar size="32px" class="q-mr-sm">
            <img src="https://i.imgur.com/4M34hi2.png" alt="Logo" />
          </q-avatar>
          FS Tours
        </q-toolbar-title>
        <q-select
          v-model="selectedTour"
          :options="tourOptions"
          option-value="value"
          option-label="label"
          label="Select Tour"
          dense
          outlined
          class="q-ml-md"
          style="min-width: 200px"
          :loading="store.loading"
          @update:model-value="onTourSelected"
          clearable
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
      </q-toolbar>
    </q-header>

    <q-page-container>
      <router-view />
    </q-page-container>

    <q-footer elevated class="bg-dark text-white">
      <div class="q-pa-sm q-gutter-sm row items-center justify-between">
        <div>FS Tours &copy; {{ new Date().getFullYear() }}</div>
        <div class="text-caption">Map last updated: {{ lastUpdated }}</div>
      </div>
    </q-footer>
  </q-layout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useFsToursStore } from 'stores/fstours'
import { useQuasar } from 'quasar'

const store = useFsToursStore()
const $q = useQuasar()
const selectedTour = ref(null)
const tourOptions = computed(() => {
  return store.tours.map((tour) => ({
    label: tour.tour_description,
    value: tour.tour_id,
  }))
})

const lastUpdated = ref('--:--:--Z')

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
