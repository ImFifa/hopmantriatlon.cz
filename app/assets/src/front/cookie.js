import "./cookieconsent.min";

document.addEventListener("DOMContentLoaded", () => {
    window.cookieconsent.initialise({
        "palette": {
            "popup": {
                "background": "#191919",
                "text": "#ffffff"
            },
            "button": {
                "text": "#ffffff",
                "background": "#44a936"
            }
        },
        "theme": "edgeless",
        "position": "bottom-left",
        "content": {
            "message": "Pro zajištění nejlepší uživatelské zkušenosti využívá naše stránka Cookies.",
            "dismiss": "Rozumím",
            "link": ""
        }
    })
});
