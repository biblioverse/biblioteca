import { createApp } from 'vue'
import ReadEBook from './ReadEbook.vue'

document.addEventListener('DOMContentLoaded', () => {
    const file = document.getElementById('mount').getAttribute('data-file')
    createApp(ReadEBook, {file: file}).mount('#mount');
});
