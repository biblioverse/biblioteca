<template>
  <div id="area" style="width: 100vw; height: 100vh; min-height: 95vh;">
    <vue-reader
        :url="props.file"
        :showToc="true"
        :getRendition="getRendition"
        :backgroundColor="props.bgColor"
        @update:location="locationChange"
        :location="getInitialLocation()"
    />
  </div>
</template>

<script setup>
const props = defineProps({
  'file': String,
  'css': String,
  'bgColor': String,
  'percent': String,
  'progressionUrl': String
})
import { VueReader } from 'vue-book-reader'
const initialCfi = new URLSearchParams(window.location.search).get('cfi');

function debounce(func, delay) {
  let timeoutId;
  return function(...args) {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => func.apply(this, args), delay);
  };
}

const locationChange = debounce((detail) => {
  const { fraction } = detail
  console.log('locationChange', fraction, detail)
  history.pushState({fraction: fraction, }, document.title, "?cfi=" + encodeURIComponent(detail.cfi) + "#page="+ detail.location.current+"&percent=" + (fraction * 100).toFixed(2));

  if(props.progressionUrl !== ""){
    fetch(props.progressionUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({percent: fraction, cfi: detail.cfi}),
    })
    .then(response => response.json())
    .catch((error) => {
      console.error('Error:', error);
    });
  }

}, 500);

const getInitialLocation = () => {
    // If we have a cfi in the url we use it
    if(initialCfi){
      return initialCfi
    }
    // If we know the current progress in percent we use it
    if(props.percent !== "undefined"){
      const percent = parseFloat(props.percent)
      if(!isNaN(percent)){
        return {fraction: percent};
      }
    }

    return null;
}

const getRendition = async (rendition) => {
  rendition.renderer.setStyles([props.css])
}

</script>
