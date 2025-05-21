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
        <LMarker :lat-lng="leg.origin_coords" :icon="numberedIcon(leg.sequence)">
          <LTooltip permanent>{{ leg.origin }} - {{ leg.origin_name }}</LTooltip>
        </LMarker>

        <!-- Destination Marker - Only rendered if it's not an origin of another leg -->
        <LMarker
          v-if="!isLocationAnOrigin(leg.destination_coords, index)"
          :lat-lng="leg.destination_coords"
          :icon="emptyIcon()"
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
            className: 'custom-polyline',
            opacity: 0.9,
          }"
        >
          <LPopup>
            <div class="q-pa-sm">
              <div class="flex items-center justify-between">
                <div class="text-h6">{{ leg.origin }} -> {{ leg.destination }}</div>
                <div class="q-gutter-x-sm">
                  <q-btn
                    square
                    glossy
                    size="xs"
                    color="secondary"
                    icon="edit"
                    style="border: 1px solid #f2c037"
                  />
                  <q-btn
                    square
                    glossy
                    size="xs"
                    color="negative"
                    icon="delete"
                    style="border: 1px solid #c10015"
                  />
                </div>
              </div>
              <q-separator class="q-my-xs" />

              <q-table
                class="leg-info-table"
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
                  style="border: 1px solid #f19914"
                />
                <q-btn
                  v-if="leg.link2"
                  size="sm"
                  color="warning"
                  icon="launch"
                  :href="leg.link2"
                  target="_blank"
                  label="Link 2"
                  style="border: 1px solid #f19914"
                />
                <q-btn
                  v-if="leg.link3"
                  size="sm"
                  color="warning"
                  icon="launch"
                  :href="leg.link3"
                  target="_blank"
                  label="Link 3"
                  style="border: 1px solid #f19914"
                />
              </div>
            </div>
          </LPopup>
        </LPolyline>
      </template>
    </LMap>

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
const map = ref(null)

// Table definition for leg details
const legDetailsColumns = [
  {
    name: 'label',
    field: 'label',
    align: 'left',
    label: 'Field',
    style: 'width: 100px; color: #ffffff;',
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

const numberedIcon = (number) =>
  L.divIcon({
    className: 'custom-marker',
    html: `<div class="marker-number">${number}</div>`,
    iconSize: [25, 25],
    iconAnchor: [15, 15],
  })

const emptyIcon = () =>
  L.divIcon({
    className: 'custom-marker destination-marker',
    html: `<div class="marker-number"></div>`,
    iconSize: [25, 25],
    iconAnchor: [15, 15],
  })

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
</script>

<style>
.custom-marker {
  display: flex;
  background-color: #1f6eb8; /* Accent color */
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
  background-color: #1f6eb8; /* Accent color */
}

.marker-number {
  line-height: 12px;
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

/* Custom styling for Leaflet popups */
:deep(.leaflet-popup-content-wrapper) {
  background-color: #1d1d1d !important;
  color: white !important;
  border-radius: 4px !important;
  box-shadow: 0 3px 10px rgba(0, 0, 0, 0.5) !important;
}

:deep(.leaflet-popup-tip) {
  background-color: #1d1d1d !important;
}

:deep(.leaflet-popup-close-button) {
  color: white !important;
}

/* Styling for the leg info table in popups */
.leg-info-table {
  margin-top: 8px;
}

.leg-info-table .q-table__top,
.leg-info-table .q-table__bottom,
.leg-info-table thead tr:first-child th {
  padding: 0;
}

.leg-info-table tbody td {
  padding: 4px 8px;
  height: auto;
  color: white;
}

.leg-info-table .q-table__card {
  background: transparent;
  box-shadow: none;
}
</style>
