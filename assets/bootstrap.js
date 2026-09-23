import { startStimulusApp } from '@symfony/reprise/stimulus';

// Registers Stimulus controllers from controllers.json and in the controllers/ directory.
// The @symfony/reprise Vite plugin generates the controller list from the paths above.
export const app = startStimulusApp();

// register any custom, 3rd party controllers here
// app.register('some_controller_name', SomeImportedController);
