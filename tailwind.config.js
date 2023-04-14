/** @type {import('tailwindcss').Config} */
const defaultTheme = require("tailwindcss/defaultTheme");
const colors = require("tailwindcss/colors");

module.exports = {
    content: [
        "./assets/**/*.js",
        "./templates/**/*.html.twig",
    ],
    darkMode: "class", // or 'media' or 'class'
    theme: {
        extend: {
            colors: {
                gray: colors.neutral
            },
            fontFamily: {
                // to change, update font in _document.js
                stock: [defaultTheme.fontFamily.sans]
            },
            aspectRatio: {
                "4/3": "4 / 3",
                "3/2": "3 / 2",
                "2/3": "2 / 3",
                "9/16": "9 / 16"
            }
        }
    },
    plugins: [],
}

