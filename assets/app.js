import './bootstrap.js';
import './styles/global.scss';
import * as bootstrap from 'bootstrap'


window.addEventListener('manager:flush', () => {
    setTimeout(function (){location.reload()}, 500)
});

const toastElList = document.querySelectorAll('.toast')
const toastList = [...toastElList].map(toastEl => new bootstrap.Toast(toastEl).show())

const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl))