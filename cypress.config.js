import { defineConfig } from "cypress";
import "dotenv/config";

export default defineConfig({
    env: {
        url: process.env.APP_URL,
    },
    chromeWebSecurity: false,
    watchForFileChanges: false,
    e2e: {
        setupNodeEvents(on, config) {},
    },
});
