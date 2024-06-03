<template>
  <div id="area" style="width: 100vw; height: 100vh; min-height: 95vh;">
    <vue-reader
        :url="props.file"
        :showToc="true"
        :getRendition="getRendition"
        backgroundColor="#000"
        @update:location="locationChange"
        :location="getInitialLocation()"
    />
  </div>
</template>

<script setup>
const props = defineProps({'file': String})
import { VueReader } from 'vue-book-reader'
const initialCfi = new URLSearchParams(window.location.search).get('cfi');

const locationChange = (detail) => {
  const { fraction } = detail
  console.log('locationChange', fraction, detail)
  history.pushState({fraction: fraction, }, document.title, "?cfi=" + encodeURIComponent(detail.cfi) + "#page="+ detail.location.current+"&percent=" + (fraction * 100).toFixed(2));
}

const getInitialLocation = () => {
    return initialCfi ?? null
}
const getRendition = async (rendition) => {
  rendition.renderer.setStyles([
    `
    html {
      background: #000;
      color: #fff;
    }`,
  ])
}

</script>
