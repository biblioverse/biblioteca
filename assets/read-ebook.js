import { createApp } from 'vue'
import ReadEBook from './ReadEbook.vue'

document.addEventListener('DOMContentLoaded', () => {
    const mountId = 'vue-book-reader';
    const file = document.getElementById(mountId).getAttribute('data-file')
    const css = document.getElementById(mountId).getAttribute('data-css')
    const bgColor = document.getElementById(mountId).getAttribute('data-background-color')
    const percent = document.getElementById(mountId).getAttribute('data-percent')
    const progressionUrl = document.getElementById(mountId).getAttribute('data-progressionUrl')
    createApp(ReadEBook, {
        file: file,
        css: css,
        bgColor: bgColor,
        percent: percent,
        progressionUrl: progressionUrl
    }).mount(`#${mountId}`);
});
