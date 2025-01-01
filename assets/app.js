import './bootstrap.js';
import './styles/global.scss';
import * as bootstrap from 'bootstrap'


window.addEventListener('manager:flush', () => {

    console.log('flush asked')
    setTimeout(function (){location.reload()}, 500)

});

const toastElList = document.querySelectorAll('.toast')
const toastList = [...toastElList].map(toastEl => new bootstrap.Toast(toastEl).show())
