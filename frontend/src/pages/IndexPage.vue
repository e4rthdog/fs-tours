<template>
  <q-page class="q-pa-none" style="height: 100vh; width: 100vw; overflow: hidden">
    <LMap ref="map" style="height: 100vh; width: 100vw" :zoom="3" :center="[40, 27]">
      <LTileLayer
        url="https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png"
        layer-type="base"
        name="OpenStreetMap"
        attribution="&copy; OpenStreetMap contributors"
      />
      <template v-for="(leg, index) in store.legs" :key="leg.id">
        <!-- Origin Marker -->
        <LMarker :lat-lng="leg.origin_coords">
          <LTooltip permanent>{{ leg.sequence }}. {{ leg.origin }} - {{ leg.origin_name }}</LTooltip>
        </LMarker>

        <!-- Destination Marker - Only rendered if it's not an origin of another leg -->
        <LMarker
          v-if="!isLocationAnOrigin(leg.destination_coords, index)"
          :lat-lng="leg.destination_coords"
        >
          <LTooltip permanent>{{ leg.destination }} - {{ leg.destination_name }}</LTooltip>
        </LMarker>

        <!-- Polyline connecting Origin and Destination -->
        <LPolyline
          :lat-lngs="[leg.origin_coords, leg.destination_coords]"
          :weight="3"
          color="#1f6eb8"
          :options="{
            smoothFactor: 1.5,
            interactive: true,
            bubblingMouseEvents: false,
            opacity: 0.9,
          }"
        >
          <LPopup>
            <div class="q-pa-sm bg-dark text-white">
              <div class="flex items-center justify-between">
                <div class="text-h6">{{ leg.origin }} -> {{ leg.destination }}</div>
                <div class="q-gutter-x-sm">
                  <q-btn flat round dense icon="edit" title="Edit Leg" />
                  <q-btn
                    flat
                    round
                    dense
                    icon="delete"
                    title="Delete Leg"
                    @click="confirmDeleteLeg(leg)"
                    :loading="store.loading"
                  />
                </div>
              </div>

              <q-table
                class="q-mt-sm bg-transparent text-white"
                :rows="legDetailsToRows(leg)"
                :columns="legDetailsColumns"
                hide-bottom
                hide-header
                dense
                flat
                dark
                :pagination="{ rowsPerPage: 0 }"
                separator="horizontal"
              />

              <!-- Link buttons -->
              <div class="q-mt-sm q-gutter-sm row">
                <q-btn
                  v-if="leg.link1"
                  size="sm"
                  color="warning"
                  icon="launch"
                  :href="leg.link1"
                  target="_blank"
                  label="Link 1"
                  outline
                />
                <q-btn
                  v-if="leg.link2"
                  size="sm"
                  color="warning"
                  icon="launch"
                  :href="leg.link2"
                  target="_blank"
                  label="Link 2"
                  outline
                />
                <q-btn
                  v-if="leg.link3"
                  size="sm"
                  color="warning"
                  icon="launch"
                  :href="leg.link3"
                  target="_blank"
                  label="Link 3"
                  outline
                />
              </div>
            </div>
          </LPopup>
        </LPolyline>
      </template>
    </LMap>

    <!-- No Tours Selected Message -->
    <div
      v-if="!store.selectedTour && !store.loading"
      class="fixed-center z-max"
      style="width: 400px"
    >
      <q-card flat bordered class="q-pa-md bg-dark text-white">
        <q-card-section>
          <div class="text-h6">Welcome to FS Tours</div>
          <q-separator dark class="q-my-md" />
          <p>Please select a tour from the dropdown in the header to view flight legs.</p>
        </q-card-section>
      </q-card>
    </div>

    <!-- Loading Overlay -->
    <div
      v-if="store.loading"
      class="fixed-center bg-dark text-white q-pa-md rounded-borders shadow-4 z-max"
    >
      <q-spinner-dots color="primary" size="40px" />
      <div class="q-mt-sm text-center">{{ store.loading ? 'Processing...' : 'Loading...' }}</div>
    </div>
  </q-page>
</template>

<script setup>
import { LMap, LTileLayer, LPolyline, LMarker, LTooltip, LPopup } from '@vue-leaflet/vue-leaflet'
import L from 'leaflet'
import { useFsToursStore } from 'stores/fstours'
import { onMounted, ref, watch } from 'vue'
import { useQuasar } from 'quasar'

const store = useFsToursStore()
const $q = useQuasar()
const map = ref(null)

// Table definition for leg details
const legDetailsColumns = [
  {
    name: 'label',
    field: 'label',
    align: 'left',
    label: 'Field',
    style: 'width: 100px; font-weight: bold;',
  },
  { name: 'value', field: 'value', align: 'left', label: 'Value' },
]

// Convert leg data to row format for q-table
const legDetailsToRows = (leg) => {
  const rows = [
    { label: 'Tour', value: leg.tour_description },
    { label: 'Leg', value: leg.sequence },
  ]

  if (leg.aircraft_model) rows.push({ label: 'Aircraft', value: leg.aircraft_model })
  if (leg.route) rows.push({ label: 'Route', value: leg.route })
  if (leg.comments) rows.push({ label: 'Comments', value: leg.comments })
  if (leg.flight_date) rows.push({ label: 'Date', value: formatDate(leg.flight_date) })

  return rows
}

// Function to check if a location is an origin in any leg
const isLocationAnOrigin = (coords, currentIndex) => {
  return store.legs.some(
    (leg, idx) =>
      idx !== currentIndex &&
      leg.origin_coords[0] === coords[0] &&
      leg.origin_coords[1] === coords[1],
  )
}

const formatDate = (dateString) => {
  if (!dateString) return ''

  try {
    const date = new Date(dateString)
    return date.toLocaleDateString('el')
  } catch {
    return dateString
  }
}

// Fix Leaflet's default icon paths
delete L.Icon.Default.prototype._getIconUrl

L.Icon.Default.mergeOptions({
  iconRetinaUrl: new URL('leaflet/dist/images/marker-icon-2x.png', import.meta.url).href,
  iconUrl: new URL('leaflet/dist/images/marker-icon.png', import.meta.url).href,
  shadowUrl: new URL('leaflet/dist/images/marker-shadow.png', import.meta.url).href,
})

onMounted(async () => {
  // If a tour is already selected, fetch its legs
  if (store.selectedTour) {
    try {
      await store.fetchTourLegs(store.selectedTour)

      // If legs are loaded and map is ready, zoom to the first leg
      if (store.legs.length > 0 && map.value) {
        // Get the first leg
        const firstLeg = store.legs[0]

        // Set the map center to the origin coordinates of the first leg
        map.value.leafletObject.setView(firstLeg.origin_coords, 6)
      }
    } catch (err) {
      $q.notify({
        type: 'negative',
        message: `Error: ${err.message}`,
      })
    }
  }
})

// Watch for changes in the selected tour to update the map
watch(
  () => store.selectedTour,
  async (newTourId) => {
    if (newTourId) {
      try {
        await store.fetchTourLegs(newTourId)

        // If legs are loaded and map is ready, zoom to the first leg
        if (store.legs.length > 0 && map.value) {
          // Get the first leg
          const firstLeg = store.legs[0]

          // Set the map center to the origin coordinates of the first leg
          map.value.leafletObject.setView(firstLeg.origin_coords, 6)
        }
      } catch (err) {
        $q.notify({
          type: 'negative',
          message: `Error: ${err.message}`,
        })
      }
    }
  },
)

// Function to handle leg deletion with confirmation
const confirmDeleteLeg = (leg) => {
  $q.dialog({
    title: 'Confirm Deletion',
    message: `Are you sure you want to delete the leg from ${leg.origin} to ${leg.destination}?`,
    cancel: {
      label: 'Cancel',
      flat: true,
    },
    ok: {
      label: 'Delete',
      color: 'negative',
    },
    persistent: true,
    dark: true,
  }).onOk(async () => {
    try {
      store.loading = true
      const response = await store.deleteLeg(leg.id)

      // Close any open popups on the map
      if (map.value && map.value.leafletObject) {
        map.value.leafletObject.closePopup()
      }

      // Show success message
      $q.notify({
        type: 'positive',
        message: response.message || 'Leg deleted successfully',
        position: 'top',
        timeout: 2000,
      })

      // Refresh tour legs after deletion
      if (store.selectedTour) {
        await store.fetchTourLegs(store.selectedTour)
      }
    } catch (err) {
      // Show error message
      $q.notify({
        type: 'negative',
        message: err.message || 'Failed to delete leg',
        position: 'top',
        timeout: 3000,
      })
    } finally {
      store.loading = false
    }
  })
}
</script>

<style>
/* Override Leaflet popups to use dark theme */
:deep(.leaflet-popup-content-wrapper) {
  background-color: var(--q-dark) !important;
  color: white !important;
  border-radius: 4px !important;
}

:deep(.leaflet-popup-tip) {
  background-color: var(--q-dark) !important;
}

:deep(.leaflet-popup-close-button) {
  color: white !important;
}
</style>
