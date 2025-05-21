<template>
  <q-page class="q-pa-none" style="height: 100vh; width: 100vw; overflow: hidden">
    <LMap ref="map" style="height: 100vh; width: 100vw" :zoom="7" :center="[40, 27]">
      <LTileLayer
        url="https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png"
        layer-type="base"
        name="OpenStreetMap"
        attribution="&copy; OpenStreetMap contributors"
      />
      <template v-for="leg in store.legs" :key="leg.id">
        <!-- Origin Marker -->
        <LMarker :lat-lng="leg.origin_coords" :icon="numberedIcon(leg.id)">
          <LTooltip permanent>{{ leg.origin }}</LTooltip>
        </LMarker>

        <!-- Destination Marker -->
        <LMarker :lat-lng="leg.destination_coords" :icon="emptyIcon()">
          <LTooltip permanent>{{ leg.destination }}</LTooltip>
        </LMarker>

        <!-- Polyline connecting Origin and Destination -->
        <LPolyline :lat-lngs="[leg.origin_coords, leg.destination_coords]">
          <LTooltip permanent>{{ leg.origin }} to {{ leg.destination }}</LTooltip>
        </LPolyline>
      </template>
    </LMap>
  </q-page>
</template>

<script setup>
import { LMap, LTileLayer, LPolyline, LMarker, LTooltip } from '@vue-leaflet/vue-leaflet'
import L from 'leaflet'
import { useFsToursStore } from 'stores/fstours'
import { onMounted } from 'vue'
import { useQuasar } from 'quasar'

const store = useFsToursStore()
const $q = useQuasar()

const numberedIcon = (number) =>
  L.divIcon({
    className: 'custom-marker',
    html: `<div class="marker-number">${number}</div>`,
    iconSize: [25, 25],
    iconAnchor: [10, 10],
  })

const emptyIcon = () =>
  L.divIcon({
    className: 'custom-marker',
    html: `<div class="marker-number"></div>`,
    iconSize: [25, 25],
    iconAnchor: [10, 10],
  })

onMounted(async () => {
  await store.fetchLegs()
  if (store.error) {
    $q.notify({
      type: 'negative',
      message: `Error: ${store.error}`,
    })
  } else {
    $q.notify({
      type: 'positive',
      message: `Loaded ${store.legs.length} legs`,
    })
    console.log('Legs:', store.legs) // TO BE REMOVED
  }
})

// Fix Leaflet's default icon paths
delete L.Icon.Default.prototype._getIconUrl

L.Icon.Default.mergeOptions({
  iconRetinaUrl: new URL('leaflet/dist/images/marker-icon-2x.png', import.meta.url).href,
  iconUrl: new URL('leaflet/dist/images/marker-icon.png', import.meta.url).href,
  shadowUrl: new URL('leaflet/dist/images/marker-shadow.png', import.meta.url).href,
})
</script>

<style>
.custom-marker {
  display: flex;
  align-items: center;
  justify-content: center;
  background: #1976d2;
  color: #fff;
  border-radius: 50%;
  width: 30px;
  height: 30px;
  border: 1px solid #fff;
  font-weight: bold;
  font-size: 10px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
}
.marker-number {
  line-height: 10px;
}
</style>
