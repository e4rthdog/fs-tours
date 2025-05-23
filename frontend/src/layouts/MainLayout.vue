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
            <q-btn flat round dense icon="add" title="Create Tour" />
            <q-btn flat round dense icon="edit" title="Edit Tour" />
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
  </q-layout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useFsToursStore } from 'stores/fstours'
import { useQuasar } from 'quasar'

const store = useFsToursStore()
const $q = useQuasar()
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
