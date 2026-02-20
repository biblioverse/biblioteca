import { startStimulusApp, registerControllers } from "vite-plugin-symfony/stimulus/helpers";

const app = startStimulusApp();
registerControllers(
    app,
    import.meta.glob('./controllers/*_controller.js', {
        query: "?stimulus",
        /**
         * always true, the `lazy` behavior is managed internally with
         * import.meta.stimulusFetch (see reference)
         */
        eager: true,
    })
)