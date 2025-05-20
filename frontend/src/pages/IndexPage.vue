<template>
  <q-page class="q-pa-none" style="height: 100vh; width: 100vw; overflow: hidden">
    <LMap ref="map" style="height: 100vh; width: 100vw" :zoom="10" :center="[37.936401, 23.9445]">
      <LTileLayer
        url="https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png"
        layer-type="base"
        name="OpenStreetMap"
        attribution="&copy; OpenStreetMap contributors"
      />
      <LCircleMarker :lat-lng="[37.9364, 23.9445]" radius="7">
        <LTooltip permanent>LGAV</LTooltip>
      </LCircleMarker>

      <LCircleMarker :lat-lng="[40.5197, 22.9709]" radius="7">
        <LTooltip permanent>LGTS</LTooltip>
      </LCircleMarker>

      <LPolyline
        :lat-lngs="[
          [37.9364, 23.9445],
          [40.5197, 22.9709],
        ]"
      >
        <LTooltip permanent>LGAV to LGTS</LTooltip>
      </LPolyline>
    </LMap>
  </q-page>
</template>

<script setup>
import { LMap, LTileLayer, LTooltip, LPolyline, LCircleMarker } from '@vue-leaflet/vue-leaflet'

import L from 'leaflet'

// Fix Leaflet's default icon paths
delete L.Icon.Default.prototype._getIconUrl

L.Icon.Default.mergeOptions({
  iconRetinaUrl: new URL('leaflet/dist/images/marker-icon-2x.png', import.meta.url).href,
  iconUrl: new URL('leaflet/dist/images/marker-icon.png', import.meta.url).href,
  shadowUrl: new URL('leaflet/dist/images/marker-shadow.png', import.meta.url).href,
})
</script>
