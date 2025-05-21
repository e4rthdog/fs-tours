<template>
  <q-page class="q-pa-none" style="height: 100vh; width: 100vw; overflow: hidden">
    <LMap ref="map" style="height: 100vh; width: 100vw" :zoom="7" :center="[40, 27]">
      <LTileLayer
        url="https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png"
        layer-type="base"
        name="OpenStreetMap"
        attribution="&copy; OpenStreetMap contributors"
      />
      <template v-for="(leg, index) in store.legs" :key="leg.id">
        <!-- Origin Marker -->
        <LMarker :lat-lng="leg.origin_coords" :icon="numberedIcon(index + 1)">
          <LPopup>
            <div class="q-pa-sm">
              <div class="text-h6">{{ leg.origin }}</div>
              <q-separator class="q-my-xs" />
              <div><strong>Tour:</strong> {{ leg.tour_description }}</div>
              <div><strong>Leg:</strong> {{ index + 1 }}</div>
              <div><strong>To:</strong> {{ leg.destination }}</div>
              <div v-if="leg.aircraft_model">
                <strong>Aircraft:</strong> {{ leg.aircraft_model }}
              </div>
              <div v-if="leg.route"><strong>Route:</strong> {{ leg.route }}</div>
              <div v-if="leg.comments"><strong>Comments:</strong> {{ leg.comments }}</div>
              <div v-if="leg.flight_date">
                <strong>Date:</strong> {{ formatDate(leg.flight_date) }}
              </div>
              <div class="q-mt-sm" v-if="leg.link1">
                <q-btn
                  size="sm"
                  color="primary"
                  outline
                  icon="launch"
                  :href="leg.link1"
                  target="_blank"
                  label="View Flight"
                />
              </div>
            </div>
          </LPopup>
          <LTooltip permanent>{{ leg.origin }} ({{ index + 1 }})</LTooltip>
        </LMarker>

        <!-- Destination Marker - Only rendered if it's not an origin of another leg -->
        <LMarker 
          v-if="!isLocationAnOrigin(leg.destination_coords, index)" 
          :lat-lng="leg.destination_coords" 
          :icon="emptyIcon()"
        >
          <LPopup>
            <div class="q-pa-sm">
              <div class="text-h6">{{ leg.destination }}</div>
              <q-separator class="q-my-xs" />
              <div><strong>Tour:</strong> {{ leg.tour_description }}</div>
              <div><strong>Arrival point for leg:</strong> {{ index + 1 }}</div>
              <div v-if="leg.comments"><strong>Comments:</strong> {{ leg.comments }}</div>
            </div>
          </LPopup>
          <LTooltip permanent>{{ leg.destination }}</LTooltip>
        </LMarker>

        <!-- Polyline connecting Origin and Destination -->
        <LPolyline
          :lat-lngs="[leg.origin_coords, leg.destination_coords]"
          :weight="3"
          @click="onLegClick(leg, index)"
        >
          <LTooltip>{{ leg.origin }} to {{ leg.destination }} (Leg {{ index + 1 }})</LTooltip>
        </LPolyline>
      </template>
    </LMap>

    <!-- Info Panel -->
    <q-card v-if="selectedLeg" class="info-panel">
      <q-card-section>
        <div class="text-h6">Flight Details</div>
      </q-card-section>
      <q-card-section>
        <div><strong>Tour:</strong> {{ selectedLeg.tour_description }}</div>
        <div><strong>Leg:</strong> {{ selectedLegIndex + 1 }}</div>
        <div><strong>From:</strong> {{ selectedLeg.origin }}</div>
        <div><strong>To:</strong> {{ selectedLeg.destination }}</div>
        <div v-if="selectedLeg.aircraft_model">
          <strong>Aircraft:</strong> {{ selectedLeg.aircraft_model }}
        </div>
        <div v-if="selectedLeg.route"><strong>Route:</strong> {{ selectedLeg.route }}</div>
        <div v-if="selectedLeg.comments"><strong>Comments:</strong> {{ selectedLeg.comments }}</div>
        <div v-if="selectedLeg.flight_date">
          <strong>Date:</strong> {{ formatDate(selectedLeg.flight_date) }}
        </div>
      </q-card-section>
      <q-card-actions align="right">
        <q-btn
          v-if="selectedLeg.link1"
          color="primary"
          flat
          label="View Flight"
          icon="launch"
          :href="selectedLeg.link1"
          target="_blank"
        />
        <q-btn
          flat
          label="Close"
          color="negative"
          icon="close"
          v-close-popup
          @click="selectedLeg = null"
        />
      </q-card-actions>
    </q-card>

    <!-- No Tours Selected Message -->
    <div v-if="!store.selectedTour && !store.loading" class="no-tours-message">
      <q-card flat bordered class="q-pa-md bg-dark text-white">
        <q-card-section>
          <div class="text-h6">Welcome to FS Tours</div>
          <q-separator dark class="q-my-md" />
          <p>Please select a tour from the dropdown in the header to view flight legs.</p>
        </q-card-section>
      </q-card>
    </div>

    <!-- Loading Overlay -->
    <div v-if="store.loading" class="loading-overlay">
      <q-spinner-dots color="primary" size="40px" />
      <div class="q-mt-sm">Loading...</div>
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
const selectedLeg = ref(null)
const selectedLegIndex = ref(-1)

// Function to check if a location is an origin in any leg
const isLocationAnOrigin = (coords, currentIndex) => {
  return store.legs.some((leg, idx) => 
    idx !== currentIndex && 
    leg.origin_coords[0] === coords[0] && 
    leg.origin_coords[1] === coords[1]
  )
}

const formatDate = (dateString) => {
  if (!dateString) return ''

  try {
    const date = new Date(dateString)
    return date.toLocaleDateString()
  } catch {
    return dateString
  }
}

const numberedIcon = (number) =>
  L.divIcon({
    className: 'custom-marker',
    html: `<div class="marker-number">${number}</div>`,
    iconSize: [30, 30],
    iconAnchor: [15, 15],
  })

const emptyIcon = () =>
  L.divIcon({
    className: 'custom-marker destination-marker',
    html: `<div class="marker-number"></div>`,
    iconSize: [20, 20],
    iconAnchor: [10, 10],
  })

const onLegClick = (leg, index) => {
  selectedLeg.value = leg
  selectedLegIndex.value = index
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
      selectedLeg.value = null
      selectedLegIndex.value = -1
    }
  },
)
</script>

<style>
.custom-marker {
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  border-radius: 50%;
  width: 30px;
  height: 30px;
  border: 1px solid #fff;
  font-weight: bold;
  font-size: 12px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.5);
}

.destination-marker {
  width: 20px;
  height: 20px;
}

.marker-number {
  line-height: 12px;
}

.info-panel {
  position: absolute;
  bottom: 20px;
  right: 20px;
  width: 300px;
  z-index: 1000;
  background: rgba(40, 40, 40, 0.9);
  color: white;
  border-radius: 8px;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.5);
}

.no-tours-message {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  z-index: 1000;
  width: 400px;
  text-align: center;
}

.loading-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.7);
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  z-index: 2000;
  color: white;
}
</style>
