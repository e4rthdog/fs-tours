<template>
  <q-page class="q-pa-none map-container">
    <LMap ref="map" class="full-map" :zoom="3" :center="[40, 27]" @ready="onMapReady">
      <LTileLayer
        url="https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png"
        layer-type="base"
        name="OpenStreetMap"
        attribution="&copy; OpenStreetMap contributors"
      />
      <template v-for="(leg, index) in store.legs" :key="leg.id + '-' + leg.sequence">
        <!-- Origin CircleMarker -->
        <LCircleMarker
          :lat-lng="leg.origin_coords"
          :radius="5"
          :color="'#1976d2'"
          :fill-color="'#1976d2'"
          :fill-opacity="0.9"
          :weight="2"
        >
          <LTooltip permanent> {{ leg.origin }} - {{ leg.origin_name }} </LTooltip>
        </LCircleMarker>

        <!-- Origin ICAO Label - Only show at zoom threshold and above -->
        <LMarker
          v-if="currentZoom >= ZOOM_THRESHOLD"
          :lat-lng="getLabelPosition(leg.origin_coords)"
          :icon="createTextIcon(leg.origin)"
        />

        <!-- Destination CircleMarker - Only rendered if it's not an origin of another leg -->
        <LCircleMarker
          v-if="!isLocationAnOrigin(leg.destination_coords, index)"
          :lat-lng="leg.destination_coords"
          :radius="5"
          :color="'#1976d2'"
          :fill-color="'#1976d2'"
          :fill-opacity="0.9"
          :weight="2"
        >
          <LTooltip permanent> {{ leg.destination }} - {{ leg.destination_name }} </LTooltip>
        </LCircleMarker>

        <!-- Destination ICAO Label - Only rendered if it's not an origin of another leg and zoom threshold+ -->
        <LMarker
          v-if="!isLocationAnOrigin(leg.destination_coords, index) && currentZoom >= ZOOM_THRESHOLD"
          :lat-lng="getLabelPosition(leg.destination_coords)"
          :icon="createTextIcon(leg.destination)"
        />

        <!-- Polyline connecting Origin and Destination -->
        <LPolyline
          ref="polyline"
          :lat-lngs="[leg.origin_coords, leg.destination_coords]"
          :weight="3"
          color="#1f6eb8"
          :options="{
            smoothFactor: 1.5,
            interactive: false,
            bubblingMouseEvents: false,
            opacity: 0.9,
          }"
        >
          <LPopup>
            <LegInfo
              :leg="leg"
              :loading="store.loading"
              :isAdmin="store.isAdmin"
              @edit="openEditDialog"
              @delete="confirmDeleteLeg"
            />
          </LPopup>
        </LPolyline>

        <!-- Sequence Marker at polyline center -->
        <LMarker
          :lat-lng="getPolylineCenter(leg.origin_coords, leg.destination_coords)"
          :icon="createSequenceIcon(leg.sequence, leg.origin_coords, leg.destination_coords)"
          @click="openLegPopup(leg)"
        />
      </template>
    </LMap>

    <!-- No Tours Selected Message -->
    <div
      v-if="!store.selectedTour && !store.loading"
      class="fixed-center"
      style="width: 400px; z-index: 1000"
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
    <div v-if="store.loading" class="fixed-center bg-dark text-white q-pa-md rounded-borders z-top">
      <q-spinner-dots color="primary" size="40px" />
      <div class="q-mt-sm text-center">{{ store.loading ? 'Processing...' : 'Loading...' }}</div>
    </div>

    <!-- Leg Info Dialog -->
    <q-dialog v-model="legInfoDialog.show" dark>
      <q-card flat style="min-width: 400px; max-width: 90vw">
        <LegInfo
          :leg="legInfoDialog.leg"
          :loading="store.loading"
          :isAdmin="store.isAdmin"
          @edit="openEditDialogFromInfo"
          @delete="confirmDeleteLegFromInfo"
          @close="legInfoDialog.show = false"
        />
      </q-card>
    </q-dialog>

    <!-- Edit Leg Dialog -->
    <q-dialog v-model="editDialog.show" persistent dark>
      <q-card flat style="min-width: 400px; max-width: 90vw">
        <LegForm
          v-model="editDialog.form"
          :loading="store.loading"
          :isEdit="true"
          @submit="submitEditLeg"
          @cancel="editDialog.show = false"
        />
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import {
  LMap,
  LTileLayer,
  LPolyline,
  LTooltip,
  LPopup,
  LCircleMarker,
  LMarker,
} from '@vue-leaflet/vue-leaflet'
import L from 'leaflet'
import { useFsToursStore } from 'stores/fstours'
import { onMounted, ref, watch, reactive } from 'vue'
import { useQuasar } from 'quasar'
import { useRoute } from 'vue-router'
import LegForm from 'components/LegForm.vue'
import LegInfo from 'components/LegInfo.vue'

const store = useFsToursStore()
const $q = useQuasar()
const route = useRoute()
const map = ref(null)
const currentZoom = ref(3) // Track current zoom level

// Configurable zoom level threshold for showing labels and sequence markers
const ZOOM_THRESHOLD = 6

// Function to get label position based on zoom level
const getLabelPosition = (coords) => {
  // Calculate offset based on zoom level
  // Higher zoom = smaller offset, lower zoom = larger offset
  const baseOffset = 0.05 // Reduced from 0.1 to bring labels closer
  const zoomFactor = Math.max(0.5, 6 - currentZoom.value) // Reduced factor range
  const offset = baseOffset * zoomFactor

  return [coords[0] + offset, coords[1] + offset]
}

// Handle map ready event
const onMapReady = () => {
  if (map.value && map.value.leafletObject) {
    // Set initial zoom level
    currentZoom.value = map.value.leafletObject.getZoom()
  }
}

// Create a custom arrow icon for the sequence number and route direction
const createSequenceIcon = (sequence, originCoords, destCoords) => {
  // Calculate the bearing/heading from origin to destination
  const bearing = calculateBearing(originCoords, destCoords)

  return new L.DivIcon({
    html: `<div class='sequence-arrow' style='transform: rotate(${bearing}deg)'>
             <span class='sequence-number' style='transform: rotate(${-bearing}deg)'>${sequence}</span>
           </div>`,
    className: 'sequence-icon',
    iconSize: [40, 20],
    iconAnchor: [20, 10],
  })
}

// Calculate bearing between two coordinates (input: [lat, lng])
const calculateBearing = (start, end) => {
  const startLat = (start[0] * Math.PI) / 180
  const startLng = (start[1] * Math.PI) / 180
  const endLat = (end[0] * Math.PI) / 180
  const endLng = (end[1] * Math.PI) / 180

  const dLng = endLng - startLng

  const y = Math.sin(dLng) * Math.cos(endLat)
  const x =
    Math.cos(startLat) * Math.sin(endLat) - Math.sin(startLat) * Math.cos(endLat) * Math.cos(dLng)

  const bearing = (Math.atan2(y, x) * 180) / Math.PI
  // Convert to 0-360 degrees and adjust for CSS rotation (add 180 to point from origin to destination, subtract 90 to align with arrow design)
  return (bearing + 270) % 360
}

// Create a text icon for airport ICAO codes
const createTextIcon = (icaoCode) => {
  return new L.DivIcon({
    html: `<div class='icao-label'>${icaoCode}</div>`,
    className: 'icao-icon',
    iconSize: [40, 20],
    iconAnchor: [0, 0],
  })
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

// Function to calculate the center point of a polyline
const getPolylineCenter = (startCoords, endCoords) => {
  const lat = (startCoords[0] + endCoords[0]) / 2
  const lng = (startCoords[1] + endCoords[1]) / 2
  return [lat, lng]
}

// Function to open leg popup programmatically
const openLegPopup = (leg) => {
  legInfoDialog.leg = leg
  legInfoDialog.show = true
}

// Helper functions for leg info dialog actions
const openEditDialogFromInfo = (leg) => {
  legInfoDialog.show = false
  openEditDialog(leg)
}

const confirmDeleteLegFromInfo = (leg) => {
  legInfoDialog.show = false
  confirmDeleteLeg(leg)
}

// Fix Leaflet's default icon paths
delete L.Icon.Default.prototype._getIconUrl

L.Icon.Default.mergeOptions({
  iconRetinaUrl: new URL('leaflet/dist/images/marker-icon-2x.png', import.meta.url).href,
  iconUrl: new URL('leaflet/dist/images/marker-icon.png', import.meta.url).href,
  shadowUrl: new URL('leaflet/dist/images/marker-shadow.png', import.meta.url).href,
})

onMounted(async () => {
  // Check if there's a tour parameter in the URL and no tour is selected yet
  const tourIdFromUrl = route.params.tourId
  if (tourIdFromUrl && !store.selectedTour) {
    // Set the tour from URL parameter (this will trigger the watcher)
    store.setSelectedTour(tourIdFromUrl)
  }

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
    title: 'Confirm Leg Deletion',
    message: `Are you sure you want to delete the leg from ${leg.origin} to ${leg.destination}?`,
    cancel: {
      label: 'Cancel',
      flat: true,
      color: 'white',
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

const legInfoDialog = reactive({
  show: false,
  leg: null,
})

const editDialog = reactive({
  show: false,
  leg: null,
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

const openEditDialog = (leg) => {
  editDialog.leg = leg
  editDialog.form = {
    tour_id: leg['tour-id'] || leg.tour_id || '',
    origin: leg.origin,
    destination: leg.destination,
    aircraft: leg.aircraft || '',
    route: leg.route || '',
    comments: leg.comments || '',
    link1: leg.link1 || '',
    link2: leg.link2 || '',
    link3: leg.link3 || '',
    flight_date: leg.flight_date || '',
  }
  editDialog.show = true
}

const submitEditLeg = async () => {
  try {
    store.loading = true
    await store.updateLeg({
      id: editDialog.leg.id,
      ...editDialog.form,
    })
    editDialog.show = false
    if (store.selectedTour) {
      await store.fetchTourLegs(store.selectedTour)
    }
    $q.notify({ type: 'positive', message: 'Leg updated', position: 'top', timeout: 2000 })
  } catch (err) {
    $q.notify({
      type: 'negative',
      message: err.message || 'Failed to update leg',
      position: 'top',
      timeout: 3000,
    })
  } finally {
    store.loading = false
  }
}
</script>

<style scoped>
/* Map container styling */
.map-container {
  position: relative;
  height: calc(100vh - 100px); /* Subtract approximate header + footer height */
  width: 100vw;
  overflow: hidden;
}

.full-map {
  height: 100% !important;
  width: 100% !important;
  min-height: 400px;
}

/* Styling for sequence arrow markers */
:deep(.sequence-icon) {
  background: none !important;
  border: none !important;
  box-shadow: none !important;
}

:deep(.sequence-arrow) {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 20px;
  background: var(--q-secondary);
  color: white;
  font-weight: bold;
  font-size: 12px;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
  position: relative;
  transform-origin: center;
  clip-path: polygon(0 0, 75% 0, 100% 50%, 75% 100%, 0 100%);
}

:deep(.sequence-number) {
  display: inline-block;
  transform-origin: center;
}

/* Styling for ICAO code labels */
:deep(.icao-icon) {
  background: none !important;
  border: none !important;
  box-shadow: none !important;
}

:deep(.icao-label) {
  background: rgba(0, 0, 0, 0.8);
  color: white;
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: bold;
  text-align: center;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
  white-space: nowrap;
}

/* Hide the default Leaflet popup wrapper */
:deep(.leaflet-popup-content-wrapper) {
  background: transparent !important;
  box-shadow: none !important;
  border: none !important;
  padding: 0 !important;
  margin: 0 !important;
}

:deep(.leaflet-popup-tip) {
  display: none !important;
}

:deep(.leaflet-popup-content) {
  margin: 0 !important;
  padding: 0 !important;
  background: transparent !important;
}

/* Style the close button for better visibility */
:deep(.leaflet-popup-close-button) {
  position: absolute !important;
  top: 15px !important;
  right: 12px !important;
  background: rgba(0, 0, 0, 0.7) !important;
  color: white !important;
  border: none !important;
  border-radius: 50% !important;
  width: 20px !important;
  height: 20px !important;
  font-size: 12px !important;
  font-weight: bold !important;
  line-height: 18px !important;
  text-align: center !important;
  cursor: pointer !important;
  z-index: 1000 !important;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3) !important;
  transition: background-color 0.2s ease !important;
}
</style>
