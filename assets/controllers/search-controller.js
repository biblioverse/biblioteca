import { Controller } from '@hotwired/stimulus';
import { getComponent } from '@symfony/ux-live-component';

export default class extends Controller {
    static targets = [ "search", "query", "suggestions" ]
    async initialize() {
        this.component = await getComponent(this.element);

    }

    connect() {
        this.suggest({})
    }
    focus(){
        this.queryTarget.focus();
        this.suggest({})
    }

    suggest(event) {
        if(this.searchTarget.contains(document.activeElement)||(event.relatedTarget && event.relatedTarget.classList.contains('suggestion'))) {
            this.suggestionsTarget.classList.remove('d-none')
        } else {
            this.suggestionsTarget.classList.add('d-none')

        }
    }

}