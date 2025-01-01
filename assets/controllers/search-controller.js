import { Controller } from '@hotwired/stimulus';
import { getComponent } from '@symfony/ux-live-component';

export default class extends Controller {
    static targets = [ "search", "query", "suggestions" ]
    async initialize() {
        this.component = await getComponent(this.element);

    }

    connect() {
    }

    redirect() {
        const path = window.location.pathname
        if(!path.includes('/all')) {
            document.getElementById('js-main').innerHTML = ''

            history.pushState({}, '', '/all')
        }
    }

}