<template>
  <q-page class="q-pa-none" style="height: 100vh; width: 100vw; overflow: hidden">
    <LMap ref="map" style="height: 100vh; width: 100vw" :zoom="3" :center="[40, 27]">
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
          <LTooltip permanent>
            {{ leg.sequence }}. {{ leg.origin }} - {{ leg.origin_name }}
          </LTooltip>
        </LCircleMarker>

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

        <!-- Polyline connecting Origin and Destination -->
        <LPolyline
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
        </LPolyline>

        <!-- Sequence Number Marker at the midpoint of the polyline -->
        <LMarker
          :lat-lng="calculateMidpoint(leg.origin_coords, leg.destination_coords)"
          :options="{
            interactive: true,
            zIndexOffset: 1000,
            icon: createSequenceIcon(leg.sequence),
          }"
        >
          <LPopup>
            <LegInfo
              :leg="leg"
              :loading="store.loading"
              @edit="openEditDialog"
              @delete="confirmDeleteLeg"
            />
          </LPopup>
        </LMarker>
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

    <!-- Edit Leg Dialog -->
    <q-dialog v-model="editDialog.show" persistent dark>
      <q-card flat style="min-width: 400px; max-width: 90vw">
        <LegForm
          v-model="editDialog.form"
          :loading="store.loading"
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
  LMarker,
  LTooltip,
  LPopup,
  LCircleMarker,
} from '@vue-leaflet/vue-leaflet'
import L from 'leaflet'
import { useFsToursStore } from 'stores/fstours'
import { onMounted, ref, watch, reactive } from 'vue'
import { useQuasar } from 'quasar'
import LegForm from 'components/LegForm.vue'
import LegInfo from 'components/LegInfo.vue'

const store = useFsToursStore()
const $q = useQuasar()
const map = ref(null)

// Calculate the midpoint between two coordinates
const calculateMidpoint = (coord1, coord2) => {
  return [(coord1[0] + coord2[0]) / 2, (coord1[1] + coord2[1]) / 2]
}

// Create a custom icon for the sequence number
const createSequenceIcon = (sequence) => {
  return new L.DivIcon({
    html: `<div class='sequence-number'>${sequence}</div>`,
    className: 'sequence-icon',
    iconSize: [24, 24],
    iconAnchor: [12, 12],
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
    if (store.selectedTour) await store.fetchTourLegs(store.selectedTour)
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
/* Styling for sequence number markers */
:deep(.sequence-icon) {
  background: none !important;
  border: none !important;
  box-shadow: none !important;
}

:deep(.sequence-number) {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background-color: white;
  color: #1f6eb8;
  font-weight: bold;
  font-size: 12px;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
}

.sequence-icon {
  text-align: center;
  line-height: 24px;
  font-weight: bold;
  color: #1f6eb8;
}

.sequence-number {
  display: inline-block;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background-color: white;
  color: #1f6eb8;
  font-weight: bold;
  line-height: 24px;
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
