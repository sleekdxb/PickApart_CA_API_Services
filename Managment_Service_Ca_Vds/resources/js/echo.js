import Echo from "laravel-echo";

window.Echo = new Echo({
    broadcaster: "reverb",
    key: "jz5dseynicgvb50yomfd", // Use the actual app key string here
    wsHost: "api-magangment-service.pick-a-part.ca",
    wsPort: 8445,
    wssPort: 8445,
    forceTLS: true, // Ensure HTTPS/WSS is used
    encrypted: true,
    disableStats: true,
    enabledTransports: ["ws", "wss"], // Ensures fallback support
});


