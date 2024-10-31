/* Load Iframe */
// !function (e) {
window.addEventListener('DOMContentLoaded', (event) => {
    this.openPtIframe();
});

// }(window);


function openPtIframe() {
    if ("undefined" == typeof ptIframeAdded || !ptIframeAdded) {
        v = {};
        var n = document.querySelector("body")
            , overlay = document.createElement("div")
            , container = document.createElement("div")
            , iframe = document.createElement("iframe");
        overlay.setAttribute("class", "pt-iframe-overlay"),
            container.setAttribute("class", "pt-iframe-container pt-iframe-container-spinner"),
            iframe.setAttribute("id", "pt-iframe"),
            iframe.src = window.popupUrl,
            iframe.style.zIndex = "345678",
            iframe.style.display = "block",
            iframe.style.position = "fixed",
            iframe.style.width = "100%",
            iframe.style.height = "100%",
            iframe.style.top = "0px",
            iframe.style.left = "0px",
            iframe.style.margin = "0px",
            iframe.style.padding = "0px",
            iframe.style.overflowX = "hidden",
            iframe.style.overflowY = "auto",
            iframe.style.background = "transparent",
            iframe.style.border = "0px none transparent",
            iframe.style.visibility = "visible",
            iframe.style.WebkitTapHighlightColor = "transparent",
            setTimeout(function () {
                n.classList.add("pt-iframe-overlay-opened")
            }),
            container.appendChild(iframe),
            n.insertBefore(container, n.firstChild),
            n.insertBefore(overlay, container),
            v.el = iframe,
            v.container = container,
            v.overlay = overlay
    }
    window.ptIframeAdded = true;
    window.ptCloseUrl = window.popupCloseUrl;
    window.ptSucessUrl = window.popupSuccessUrl;
    console.log('success ' + window.popupSuccessUrl);
    window.addEventListener("message", receiveMessage, false);
    const iframeContainer = getPtIframe();
    iframeContainer.classList.add("active");
    //
}

function closeIframe(redirect) {
    const iframeContainer = getPtIframe();
    // iframeContainer.classList.remove("pt-iframe-container-spinner");
    iframeContainer.classList.remove("active");
    var body = document.querySelector("body");
    body.classList.remove("pt-iframe-overlay-opened"),
        v.container.removeChild(v.el),
        body.removeChild(v.container),
        body.removeChild(v.overlay),
        v = {},
        ptIframeAdded = false;
    if (!!redirect || redirect) {
        window.location.href = window.popupCloseUrl;
    }
}

function getPtIframe() {
    return document.querySelector(".pt-iframe-container");
}


function receiveMessage(event) {
    if (event.origin !== "https://ecom-staging.paytomorrow.com" && event.origin !== "https://ecom.paytomorrow.com" &&
        event.origin !== "https://consumer-staging.paytomorrow.com" && event.origin !== "https://consumer.paytomorrow.com"
    ) {
        return;
    }
    switch (event.data) {
        case "pt-close":
            if (document.getElementById('pt-iframe') != null) {
                document.getElementById('pt-iframe').setAttribute("src", "");
                closeIframe(true);
            }
            window.removeEventListener("message", receiveMessage);
            // window.location.href = ptCloseUrl;
            break;
        case "pt-finished":
            console.log('pt-finished arrived should not happen.')
            break;
    }
    if (!!event.data.message && event.data.message === "pt-confirmation") {
        if (document.getElementById('pt-iframe') != null) {
            document.getElementById('pt-iframe').setAttribute("src", "");
        }
        window.removeEventListener("message", receiveMessage);
        window.location.href = ptSucessUrl;
        //closeIframe(false);
    }
}