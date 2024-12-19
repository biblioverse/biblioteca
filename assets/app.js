import './bootstrap.js';
import './styles/global.scss';
import * as bootstrap from 'bootstrap'


window.addEventListener('manager:flush', () => {
    location.reload()
});

const toastElList = document.querySelectorAll('.toast')
const toastList = [...toastElList].map(toastEl => new bootstrap.Toast(toastEl).show())
