import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
        "./resources/js/**/*.vue",
    ],

    theme: {
        // ----------------------------------------------------------------------------------
        //                                  TYPOGRAPHY
        // ----------------------------------------------------------------------------------
        fontSize: {
            "2xs": [
                "10px",
                {
                    letterSpacing: "-0.2px",
                },
            ],
            xs: [
                "12px",
                {
                    letterSpacing: "-0.24px",
                },
            ],
            sm: [
                "14px",
                {
                    letterSpacing: "-0.28px",
                },
            ],
            base: [
                "16px",
                {
                    letterSpacing: "-0.32px",
                },
            ],
            md: [
                "20px",
                {
                    letterSpacing: "-0.4px",
                },
            ],
            lg: [
                "24px",
                {
                    letterSpacing: "-0.48px",
                },
            ],
            xl: [
                "30px",
                {
                    letterSpacing: "-0.6px",
                },
            ],
        },
        // letterSpacing: {
        //     tiny: '-0.2px',
        //     xs: '-0.24px',
        //     sm: '-0.28px',
        //     standard: '-0.32px',
        //     md: '-0.4px',
        //     lg: '-0.48px',
        //     xl: '-0.6px',
        // },
        extend: {
            fontFamily: {
                sans: ["Lexend", "sans-serif"],
            },
            // ----------------------------------------------------------------------------------
            //                                  COLORS
            // ----------------------------------------------------------------------------------
            colors: {
                'primary': {
                    25: '#FFF9F9',
                    50: '#fff1f2',
                    100: '#ffe1e2',
                    200: '#ffc7c9',
                    300: '#ffa1a5',
                    400: '#ff6a70',
                    500: '#f83b42',
                    600: '#e51d25',
                    700: '#c1141b',
                    800: '#9f151a',
                    900: '#7e171b',
                    950: '#48070a',
                },
                grey: {
                    25: "#fcfcfc",
                    50: "#f8f9fa",
                    100: "#eceff2",
                    200: "#d6dce1",
                    300: "#b2bec7",
                    400: "#889ba8",
                    500: "#697e8e",
                    600: "#546775",
                    700: "#45535f",
                    800: "#3c4750",
                    900: "#353d45",
                    950: "#23292e",
                },
                orange: {
                    25: "#fffcf9",
                    50: "#fdf6ef",
                    100: "#fbead9",
                    200: "#f6d1b2",
                    300: "#f0b281",
                    400: "#e9894e",
                    500: "#e46a2b",
                    600: "#d55121",
                    700: "#b13e1d",
                    800: "#8d321f",
                    900: "#732c1c",
                    950: "#3d140d",
                },
                yellow: {
                    25: "#fffcf9",
                    50: "#fdf6ef",
                    100: "#fbead9",
                    200: "#f6d1b2",
                    300: "#f0b281",
                    400: "#e9894e",
                    500: "#e46a2b",
                    600: "#d55121",
                    700: "#b13e1d",
                    800: "#8d321f",
                    900: "#732c1c",
                    950: "#3d140d",
                },
                blue: {
                    50: "#f0f7ff",
                    100: "#e0edfe",
                    200: "#b9dbfe",
                    300: "#7cbefd",
                    400: "#369ffa",
                    500: "#0c82eb",
                    600: "#005dba",
                    700: "#014fa3",
                    800: "#064586",
                    900: "#0b3a6f",
                    950: "#07244a",
                },
                green: {
                    50: "#f0fbea",
                    100: "#ddf5d2",
                    200: "#bcecaa",
                    300: "#93de78",
                    400: "#6dcd4e",
                    500: "#46a12b",
                    600: "#388e22",
                    700: "#2d6d1e",
                    800: "#28571d",
                    900: "#244a1d",
                    950: "#0f280b",
                },
            },
        },
    },

    plugins: [
        require("@tailwindcss/forms"),
        function ({ addUtilities }) {
            const newUtilities = {
                ".scrollnar-thin": {
                    scrollbarWidth: "thin",
                    scrollbarColor: "rgba(107, 114, 128, 1) white",
                },
                ".scrollbar-webkit": {
                    "&::-webkit-scrollbar": {
                        width: "2px",
                        height: "2px",
                    },
                    "&::-webkit-scrollbar-track": {
                        background: "#d6dce1",
                    },
                    "&::-webkit-scrollbar-thumb": {
                        backgroundColor: "#c1141b",
                        borderRadius: "20px",
                        border: "1px solid rgba(107, 114, 128, 0.3)",
                    },
                },
            };

            addUtilities(newUtilities, ["responsive", "hover"]);
        },
        require("daisyui"),
    ],
    daisyui: {
        themes: ["light"], // false: only light + dark | true: all themes | array: specific themes like this ["light", "dark", "cupcake"]
        base: false, // applies background color and foreground color for root element by default
        styled: true, // include daisyUI colors and design decisions for all components
        utils: true, // adds responsive and modifier utility classes
        prefix: "", // prefix for daisyUI classnames (components, modifiers and responsive class names. Not colors)
        logs: true, // Shows info about daisyUI version and used config in the console when building your CSS
        themeRoot: ":root", // The element that receives theme color CSS variables
    },
};
