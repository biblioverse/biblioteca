import {Controller} from '@hotwired/stimulus';
import {getComponent} from '@symfony/ux-live-component';

export default class extends Controller {
    async initialize() {
        this.component = await getComponent(this.element);

        window.removeEventListener('select:clear', this.reinitializeTomSelect);
        window.addEventListener('select:clear', this.reinitializeTomSelect);

    }

    reinitializeTomSelect(event) {
        console.log(event.detail.field);
        const selects = document.querySelectorAll(`#book-assistant-${event.detail.book} .tomselected`);
        selects.forEach(select => {
            if (select.tomselect && select.name.includes(event.detail.field)) {
                select.tomselect.sync()
            }
        })
    }
}
